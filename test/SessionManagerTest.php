<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use ArrayIterator;
use DateTime;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\Exception\RuntimeException;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Storage\SessionStorage;
use Laminas\Session\Validator\Id;
use Laminas\Session\Validator\RemoteAddr;
use LaminasTest\Session\TestAsset\Php81CompatibleStorageInterface;
use PHPUnit\Framework\TestCase;
use Traversable;

use function array_merge;
use function extension_loaded;
use function headers_sent;
use function ini_get;
use function preg_match;
use function range;
use function restore_error_handler;
use function session_destroy;
use function session_id;
use function session_name;
use function session_start;
use function session_write_close;
use function set_error_handler;
use function stristr;
use function var_export;
use function xdebug_get_headers;

use const E_WARNING;
use const PHP_SAPI;

/**
 * @preserveGlobalState disabled
 * @covers \Laminas\Session\SessionManager
 */
class SessionManagerTest extends TestCase
{
    use ReflectionPropertyTrait;

    /** @var false|string */
    public $error;

    /** @var string */
    public $cookieDateFormat = 'D, d-M-y H:i:s e';

    /** @var SessionManager */
    protected $manager;

    protected function setUp(): void
    {
        $this->error = false;
    }

    /**
     * @param int $errno
     * @param string $errstr
     */
    public function handleErrors($errno, $errstr): void
    {
        $this->error = $errstr;
    }

    /** @return false|DateTime */
    public function getTimestampFromCookie(string $cookie)
    {
        if (preg_match('/expires=([^;]+)/', $cookie, $matches)) {
            return new DateTime($matches[1]);
        }
        return false;
    }

    public function testManagerUsesSessionConfigByDefault(): void
    {
        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        self::assertInstanceOf(SessionConfig::class, $config);
    }

    public function testCanPassConfigurationToConstructor(): void
    {
        $this->manager = new SessionManager();
        $config        = new StandardConfig();
        $manager       = new SessionManager($config);
        self::assertSame($config, $manager->getConfig());
    }

    public function testManagerUsesSessionStorageByDefault(): void
    {
        $this->manager = new SessionManager();
        $storage       = $this->manager->getStorage();
        self::assertInstanceOf(SessionArrayStorage::class, $storage);
    }

    public function testCanPassStorageToConstructor(): void
    {
        $storage = new ArrayStorage();
        $manager = new SessionManager(null, $storage);
        self::assertSame($storage, $manager->getStorage());
    }

    public function testCanPassSaveHandlerToConstructor(): void
    {
        $saveHandler = new TestAsset\TestSaveHandler();
        $manager     = new SessionManager(null, null, $saveHandler);
        self::assertSame($saveHandler, $manager->getSaveHandler());
    }

    public function testCanPassValidatorsToConstructor(): void
    {
        $validators = [
            'foo',
            'bar',
        ];
        $manager    = new SessionManager(null, null, null, $validators);
        foreach ($validators as $validator) {
            $this->assertAttributeContains($validator, 'validators', $manager);
        }
    }

    public function testAttachDefaultValidatorsByDefault(): void
    {
        $manager = new SessionManager();
        $this->assertAttributeEquals([Id::class], 'validators', $manager);
    }

    public function testCanMergeValidatorsWithDefault(): void
    {
        $defaultValidators = [
            Id::class,
        ];
        $validators        = [
            'foo',
            'bar',
        ];
        $manager           = new SessionManager(null, null, null, $validators);
        $this->assertAttributeEquals(array_merge($defaultValidators, $validators), 'validators', $manager);
    }

    public function testCanDisableAttachDefaultValidators(): void
    {
        $options = [
            'attach_default_validators' => false,
        ];
        $manager = new SessionManager(null, null, null, [], $options);
        $this->assertAttributeEquals([], 'validators', $manager);
    }

    // Session-related functionality

    /**
     * @runInSeparateProcess
     */
    public function testSessionExistsReturnsFalseWhenNoSessionStarted(): void
    {
        $this->manager = new SessionManager();
        self::assertFalse($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionExistsReturnsTrueWhenSessionStarted(): void
    {
        $this->manager = new SessionManager();
        session_start();
        self::assertTrue($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionExistsReturnsTrueWhenSessionStartedThenWritten(): void
    {
        $this->manager = new SessionManager();
        session_start();
        session_write_close();
        self::assertTrue($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionExistsReturnsFalseWhenSessionStartedThenDestroyed(): void
    {
        $this->manager = new SessionManager();
        session_start();
        session_destroy();
        self::assertFalse($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionIsStartedAfterCallingStart(): void
    {
        $this->manager = new SessionManager();
        self::assertFalse($this->manager->sessionExists());
        $this->manager->start();
        self::assertTrue($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartDoesNothingWhenCalledAfterWriteCloseOperation(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $id1 = session_id();
        session_write_close();
        $this->manager->start();
        $id2 = session_id();
        self::assertTrue($this->manager->sessionExists());
        self::assertEquals($id1, $id2);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartWithOldTraversableSessionData(): void
    {
        // pre-populate session with data
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $storage          = new SessionStorage();
        // create session manager with SessionStorage that will populate object with existing session array
        $manager = new SessionManager(null, $storage);
        self::assertFalse($manager->sessionExists());
        $manager->start();
        self::assertTrue($manager->sessionExists());
        self::assertInstanceOf(Traversable::class, $_SESSION);
        self::assertEquals('value1', $_SESSION->key1);
        self::assertEquals('value2', $_SESSION->key2);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStorageContentIsPreservedByWriteCloseOperation(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $storage        = $this->manager->getStorage();
        $storage['foo'] = 'bar';
        $this->manager->writeClose();
        self::assertArrayHasKey('foo', $storage);
        self::assertEquals('bar', $storage['foo']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartCreatesNewSessionIfPreviousSessionHasBeenDestroyed(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $id1 = session_id();
        session_destroy();
        $this->manager->start();
        $id2 = session_id();
        self::assertTrue($this->manager->sessionExists());
        self::assertNotEquals($id1, $id2);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartConvertsSessionDataFromStorageInterfaceToArrayBeforeMerging(): void
    {
        $this->manager = new SessionManager();

        $key            = 'testData';
        $data           = [$key => 'test'];
        $sessionStorage = $this
            ->createMock(Php81CompatibleStorageInterface::class);
        $_SESSION       = $sessionStorage;
        $sessionStorage
            ->expects(self::once())
            ->method('toArray')
            ->willReturn($data);

        $this->manager->start();

        self::assertIsArray($_SESSION);
        self::assertArrayHasKey($key, $_SESSION);
        self::assertSame($data[$key], $_SESSION[$key]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartConvertsSessionDataFromTraversableToArrayBeforeMerging(): void
    {
        $this->manager = new SessionManager();

        $key      = 'testData';
        $data     = [$key => 'test'];
        $_SESSION = new ArrayIterator($data);

        $this->manager->start();

        self::assertIsArray($_SESSION);
        self::assertArrayHasKey($key, $_SESSION);
        self::assertSame($data[$key], $_SESSION[$key]);
    }

    /**
     * @outputBuffering disabled
     */
    public function testStartWillNotBlockHeaderSentNotices(): void
    {
        $this->manager = new SessionManager();
        if ('cli' === PHP_SAPI) {
            self::markTestSkipped('session_start() will not raise headers_sent warnings in CLI');
        }
        set_error_handler([$this, 'handleErrors'], E_WARNING);
        echo ' ';
        self::assertTrue(headers_sent());
        $this->manager->start();
        restore_error_handler();
        self::assertIsString($this->error);
        self::assertContains('already sent', $this->error);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetNameReturnsSessionName(): void
    {
        $this->manager = new SessionManager();
        $ini           = ini_get('session.name');
        self::assertEquals($ini, $this->manager->getName());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetNameRaisesExceptionOnInvalidName(): void
    {
        $this->manager = new SessionManager();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name provided contains invalid characters; must be alphanumeric only');
        $this->manager->setName('foo bar!');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetNameSetsSessionNameOnSuccess(): void
    {
        $this->manager = new SessionManager();
        $this->manager->setName('foobar');
        self::assertEquals('foobar', $this->manager->getName());
        self::assertEquals('foobar', session_name());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanSetNewSessionNameAfterSessionDestroyed(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        session_destroy();
        $this->manager->setName('foobar');
        self::assertEquals('foobar', $this->manager->getName());
        self::assertEquals('foobar', session_name());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSettingNameWhenAnActiveSessionExistsRaisesException(): void
    {
        $this->manager = new SessionManager();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set session name after a session has already started');
        $this->manager->start();
        $this->manager->setName('foobar');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroyByDefaultSendsAnExpireCookie(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $this->manager->start();
        $this->manager->destroy();

        echo '';

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName)) {
                $found = true;
            }
        }

        self::assertTrue($found, 'No session cookie found: ' . var_export($headers, true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendingFalseToSendExpireCookieWhenCallingDestroyShouldNotSendCookie(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $this->manager->start();
        $this->manager->destroy(['send_expire_cookie' => false]);

        echo '';

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName)) {
                $found = true;
            }
        }

        if ($found) {
            self::assertStringNotContainsString('expires=', $header);
        } else {
            self::assertFalse($found, 'Unexpected session cookie found: ' . var_export($headers, true));
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroyDoesNotClearSessionStorageByDefault(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $storage        = $this->manager->getStorage();
        $storage['foo'] = 'bar';
        $this->manager->destroy();
        self::assertTrue(isset($storage['foo']));
        self::assertEquals('bar', $storage['foo']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPassingClearStorageOptionWhenCallingDestroyClearsStorage(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $storage        = $this->manager->getStorage();
        $storage['foo'] = 'bar';
        $this->manager->destroy(['clear_storage' => true]);
        self::assertFalse(isset($storage['foo']));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCallingWriteCloseMarksStorageAsImmutable(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $storage        = $this->manager->getStorage();
        $storage['foo'] = 'bar';
        $this->manager->writeClose();
        self::assertTrue($storage->isImmutable());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCallingWriteCloseShouldNotAlterSessionExistsStatus(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $this->manager->writeClose();
        self::assertTrue($this->manager->sessionExists());
    }

    /**
     * @runInSeparateProcess
     */
    public function testIdShouldBeEmptyPriorToCallingStart(): void
    {
        $this->manager = new SessionManager();
        self::assertSame('', $this->manager->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testIdShouldBeMutablePriorToCallingStart(): void
    {
        $this->manager = new SessionManager();
        $this->manager->setId(self::class);
        self::assertSame(self::class, $this->manager->getId());
        self::assertSame(self::class, session_id());
    }

    /**
     * @runInSeparateProcess
     */
    public function testIdShouldNotBeMutableAfterSessionStarted(): void
    {
        $this->manager = new SessionManager();
        $this->expectException(
            RuntimeException::class
        );
        $this->manager->start();
        $origId = $this->manager->getId();
        $this->manager->setId(__METHOD__);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateIdShouldWorkAfterSessionStarted(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $origId = $this->manager->getId();
        $this->manager->regenerateId();
        self::assertNotSame($origId, $this->manager->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateIdDoesNothingWhenSessioIsNotStarted(): void
    {
        $this->manager = new SessionManager();
        $origId        = $this->manager->getId();
        $this->manager->regenerateId();
        self::assertEquals($origId, $this->manager->getId());
        self::assertEquals('', $this->manager->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegeneratingIdAfterSessionStartedShouldSendExpireCookie(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $this->manager->start();
        $this->manager->getId();
        $this->manager->regenerateId();

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName)) {
                $found = true;
            }
        }

        self::assertTrue($found, 'No session cookie found: ' . var_export($headers, true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRememberMeShouldSendNewSessionCookieWithUpdatedTimestamp(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $this->manager->start();
        $this->manager->rememberMe(18600);

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();
        $cookie  = false;

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName) && ! stristr($header, '=deleted')) {
                $found  = true;
                $cookie = $header;
            }
        }

        self::assertTrue($found, 'No session cookie found: ' . var_export($headers, true));

        $ts = $this->getTimestampFromCookie($cookie);
        if (! $ts) {
            self::fail('Cookie did not contain expiry? ' . var_export($headers, true));
        }

        self::assertGreaterThan(
            $_SERVER['REQUEST_TIME'],
            $ts->getTimestamp(),
            'Session cookie: ' . var_export($headers, 1)
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testRememberMeShouldSetTimestampBasedOnConfigurationByDefault(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $config->setRememberMeSeconds(3600);
        $ttl = $config->getRememberMeSeconds();
        $this->manager->start();
        $this->manager->rememberMe();

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();
        $cookie  = false;

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName) && ! stristr($header, '=deleted')) {
                $found  = true;
                $cookie = $header;
            }
        }

        self::assertTrue($found, 'No session cookie found: ' . var_export($headers, true));

        $ts = $this->getTimestampFromCookie($cookie);
        if (! $ts) {
            self::fail('Cookie did not contain expiry? ' . var_export($headers, true));
        }

        $compare  = $_SERVER['REQUEST_TIME'] + $ttl;
        $cookieTs = $ts->getTimestamp();
        self::assertContains($cookieTs, range($compare, $compare + 10), 'Session cookie: ' . var_export($headers, 1));
    }

    /**
     * @runInSeparateProcess
     */
    public function testForgetMeShouldSendCookieWithZeroTimestamp(): void
    {
        if (! extension_loaded('xdebug')) {
            self::markTestSkipped('Xdebug required for this test');
        }

        $this->manager = new SessionManager();
        $config        = $this->manager->getConfig();
        $config->setUseCookies(true);
        $this->manager->start();
        $this->manager->forgetMe();

        $headers = xdebug_get_headers();
        $found   = false;
        $sName   = $this->manager->getName();

        foreach ($headers as $header) {
            if (stristr($header, 'Set-Cookie:') && stristr($header, $sName) && ! stristr($header, '=deleted')) {
                $found = true;
            }
        }

        self::assertTrue($found, 'No session cookie found: ' . var_export($headers, true));
        self::assertStringNotContainsString('expires=', $header);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartingSessionThatFailsAValidatorShouldRaiseException(): void
    {
        $this->manager = new SessionManager();
        $chain         = $this->manager->getValidatorChain();
        $chain->attach('session.validate', [new TestAsset\TestFailingValidator(), 'isValid']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed');
        $this->manager->start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testResumeSessionThatFailsAValidatorShouldRaiseException(): void
    {
        $this->manager = new SessionManager();
        $this->manager->setSaveHandler(new TestAsset\TestSaveHandlerWithValidator());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed');
        $this->manager->start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionWriteCloseStoresMetadata(): void
    {
        $this->manager = new SessionManager();
        $this->manager->start();
        $storage = $this->manager->getStorage();
        $storage->setMetadata('foo', 'bar');
        $metaData = $storage->getMetadata();
        $this->manager->writeClose();
        self::assertSame($_SESSION['__Laminas'], $metaData);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionValidationDoesNotHaltOnNoopListener(): void
    {
        $this->manager   = new SessionManager();
        $validatorCalled = false;
        $validator       = static function () use (&$validatorCalled): void {
            $validatorCalled = true;
        };

        $this->manager->getValidatorChain()->attach('session.validate', $validator);

        self::assertTrue($this->manager->isValid());
        self::assertTrue($validatorCalled);
    }

    /**
     * @runInSeparateProcess
     */
    public function testProducedSessionManagerWillNotReplaceSessionSuperGlobalValues(): void
    {
        $this->manager   = new SessionManager();
        $_SESSION['foo'] = 'bar';

        $this->manager->start();

        self::assertArrayHasKey('foo', $_SESSION);
        self::assertSame('bar', $_SESSION['foo']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidatorChainSessionMetadataIsPreserved(): void
    {
        $this->manager = new SessionManager();
        $this->manager->getValidatorChain()
            ->attach('session.validate', [new RemoteAddr(), 'isValid']);

        self::assertFalse($this->manager->sessionExists());

        $this->manager->start();

        self::assertIsArray($_SESSION['__Laminas']['_VALID']);
        self::assertArrayHasKey(RemoteAddr::class, $_SESSION['__Laminas']['_VALID']);
        self::assertEquals('', $_SESSION['__Laminas']['_VALID'][RemoteAddr::class]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoteAddressValidationWillFailOnInvalidAddress(): void
    {
        $this->manager = new SessionManager();
        $this->manager->getValidatorChain()
            ->attach('session.validate', [new RemoteAddr('123.123.123.123'), 'isValid']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session validation failed');
        $this->manager->start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoteAddressValidationWillSucceedWithValidPreSetData(): void
    {
        $this->manager = new SessionManager();
        $_SESSION      = [
            '__Laminas' => [
                '_VALID' => [
                    RemoteAddr::class => '',
                ],
            ],
        ];

        $this->manager->start();

        self::assertTrue($this->manager->isValid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoteAddressValidationWillFailWithInvalidPreSetData(): void
    {
        $this->manager = new SessionManager();
        $_SESSION      = [
            '__Laminas' => [
                '_VALID' => [
                    RemoteAddr::class => '123.123.123.123',
                ],
            ],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session validation failed');
        $this->manager->start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testIdValidationWillFailOnInvalidData(): void
    {
        $this->manager = new SessionManager();
        $this->manager->getValidatorChain()
            ->attach('session.validate', [new Id('invalid-value'), 'isValid']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session validation failed');
        $this->manager->start();
    }

    /** @param non-empty-string $property */
    private function assertAttributeEquals(mixed $expected, string $property, object $object): void
    {
        $value = $this->getReflectionProperty($object, $property);
        self::assertEquals($expected, $value);
    }

    /** @param non-empty-string $property */
    private function assertAttributeContains(mixed $expected, string $property, object $object): void
    {
        $value = $this->getReflectionProperty($object, $property);
        self::assertContains($expected, $value);
    }
}
