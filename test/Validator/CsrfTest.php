<?php

declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Validator\Csrf;
use LaminasTest\Session\ReflectionPropertyTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionObject;

use function class_exists;
use function md5;
use function sprintf;
use function str_replace;
use function strtr;
use function uniqid;

final class CsrfTest extends TestCase
{
    use ReflectionPropertyTrait;

    private Csrf $validator;
    private ReflectionObject $validatorReflection;
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup session handling
        $_SESSION             = [];
        $sessionManager       = new SessionManager(
            new StandardConfig(),
            new ArrayStorage()
        );
        $this->sessionManager = $sessionManager;
        Container::setDefaultManager($sessionManager);

        $this->validator           = new Csrf();
        $this->validatorReflection = new ReflectionObject($this->validator);
    }

    private function getValidatorPropertyValue(string $property): mixed
    {
        $reflectionProperty = $this->validatorReflection->getProperty($property);
        return $reflectionProperty->getValue($this->validator);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (! class_exists(Container::class)) {
            return;
        }

        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    public function testSaltHasDefaultValueIfNotSet(): void
    {
        $salt = $this->getValidatorPropertyValue('salt');

        self::assertIsString($salt);
        self::assertSame('salt', $salt);
    }

    #[IgnoreDeprecations]
    public function testSessionContainerIsLazyLoadedIfNotSet(): void
    {
        $container = $this->getValidatorPropertyValue('session');

        self::assertInstanceOf(Container::class, $container);
    }

    public function testNameHasDefaultValue(): void
    {
        self::assertSame('csrf', $this->getValidatorPropertyValue('name'));
    }

    public function testTimeoutHasDefaultValue(): void
    {
        self::assertSame(300, $this->getValidatorPropertyValue('timeout'));
    }

    #[IgnoreDeprecations]
    public function testAllOptionsMayBeSetViaConstructor(): void
    {
        $container = new Container('foo', $this->sessionManager);
        $options   = [
            'hash'    => 'bar',
            'name'    => 'baz',
            'salt'    => 'salty',
            'session' => $container,
            'timeout' => 600,
        ];
        $validator = new Csrf($options);

        self::assertSame('bar', $this->getReflectionProperty($validator, 'hash'));
        self::assertSame('baz', $this->getReflectionProperty($validator, 'name'));
        self::assertSame('salty', $this->getReflectionProperty($validator, 'salt'));
        self::assertSame($container, $this->getReflectionProperty($validator, 'session'));
        self::assertSame(600, $this->getReflectionProperty($validator, 'timeout'));
    }

    #[IgnoreDeprecations]
    public function testHashIsGeneratedOnFirstRetrieval(): void
    {
        $hash = $this->getValidatorPropertyValue('hash');
        self::assertIsString($hash);

        $test = $this->getValidatorPropertyValue('hash');
        self::assertIsString($test);

        self::assertSame($hash, $test);
    }

    public function testSessionNameIsDerivedFromClassSaltAndName(): void
    {
        $class = $this->validator::class;
        $class = str_replace('\\', '_', $class);
        $salt  = $this->getValidatorPropertyValue('salt');
        $name  = $this->getValidatorPropertyValue('name');

        self::assertIsString($salt);
        self::assertIsString($name);

        $expected = sprintf('%s_%s_%s', $class, $salt, $name);

        self::assertSame($expected, $this->validator->getSessionName());
    }

    public function testSessionNameRemainsValidForElementBelongingToFieldset(): void
    {
        $container = new Container('foo', $this->sessionManager);
        $options   = [
            'name'    => 'fieldset[csrf]',
            'session' => $container,
        ];
        $validator = new Csrf($options);
        $salt      = $this->getReflectionProperty($validator, 'salt');
        $name      = $this->getReflectionProperty($validator, 'name');

        self::assertIsString($salt);
        self::assertIsString($name);

        $class    = $validator::class;
        $class    = str_replace('\\', '_', $class);
        $name     = strtr($name, ['[' => '_', ']' => '']);
        $expected = sprintf('%s_%s_%s', $class, $salt, $name);

        self::assertSame($expected, $validator->getSessionName());
    }

    #[IgnoreDeprecations]
    public function testIsValidReturnsFalseWhenValueDoesNotMatchHash(): void
    {
        self::assertFalse($this->validator->isValid('foo'));
    }

    #[IgnoreDeprecations]
    public function testValidationErrorMatchesNotSameConstantAndRelatedMessage(): void
    {
        $this->validator->isValid('foo');
        $messages = $this->validator->getMessages();

        self::assertArrayHasKey(Csrf::NOT_SAME, $messages);
        self::assertSame('The form submitted did not originate from the expected site', $messages[Csrf::NOT_SAME]);
    }

    #[IgnoreDeprecations]
    public function testIsValidReturnsTrueWhenValueMatchesHash(): void
    {
        $hash = $this->getValidatorPropertyValue('hash');
        self::assertIsString($hash);
        self::assertTrue($this->validator->isValid($hash));
    }

    #[IgnoreDeprecations]
    public function testSessionContainerContainsHashAfterHashHasBeenGenerated(): void
    {
        $container = $this->getValidatorPropertyValue('session');
        self::assertInstanceOf(Container::class, $container);
        self::assertNull($container->hash);

        $method       = new ReflectionMethod($this->validator::class, 'getTokenIdFromHash');
        $formatMethod = new ReflectionMethod($this->validator::class, 'formatHash');

        $hash = $this->getValidatorPropertyValue('hash');
        self::assertIsString($hash);
        $tokenId = $method->invoke($this->validator, $hash);
        self::assertIsString($tokenId);
        self::assertIsArray($container->tokenList);
        $token = $container->tokenList[$tokenId] ?? '';
        self::assertIsString($token);

        $testHash = $formatMethod->invoke($this->validator, $token, $tokenId);

        self::assertIsString($testHash);
        self::assertSame($hash, $testHash);
    }

    #[IgnoreDeprecations]
    public function testMultipleValidatorsSharingContainerGenerateDifferentHashes(): void
    {
        $validatorOne = new Csrf();
        $validatorTwo = new Csrf();

        $containerOne = $this->getReflectionProperty($validatorOne, 'session');
        self::assertInstanceOf(Container::class, $containerOne);
        $containerTwo = $this->getReflectionProperty($validatorOne, 'session');
        self::assertInstanceOf(Container::class, $containerTwo);

        self::assertSame($containerOne, $containerTwo);

        $hashOne = $this->getReflectionProperty($validatorOne, 'hash');
        self::assertIsString($hashOne);
        $hashTwo = $this->getReflectionProperty($validatorTwo, 'hash');
        self::assertIsString($hashTwo);

        self::assertNotSame($hashOne, $hashTwo);
    }

    #[IgnoreDeprecations]
    public function testCanValidateAnyHashWithinTheSameContainer(): void
    {
        $validatorOne = new Csrf();
        $validatorTwo = new Csrf();

        $hashOne = $this->getReflectionProperty($validatorOne, 'hash');
        self::assertIsString($hashOne);
        $hashTwo = $this->getReflectionProperty($validatorTwo, 'hash');
        self::assertIsString($hashTwo);

        self::assertTrue($validatorOne->isValid($hashOne));
        self::assertTrue($validatorOne->isValid($hashTwo));
        self::assertTrue($validatorTwo->isValid($hashOne));
        self::assertTrue($validatorTwo->isValid($hashTwo));
    }

    #[IgnoreDeprecations]
    public function testCannotValidateHashesOfOtherContainers(): void
    {
        $validatorOne = new Csrf();
        $validatorTwo = new Csrf(['name' => 'foo']);

        $containerOne = $this->getReflectionProperty($validatorOne, 'session');
        self::assertInstanceOf(Container::class, $containerOne);
        $containerTwo = $this->getReflectionProperty($validatorTwo, 'session');
        self::assertInstanceOf(Container::class, $containerTwo);

        self::assertNotSame($containerOne, $containerTwo);

        $hashOne = $this->getReflectionProperty($validatorOne, 'hash');
        self::assertIsString($hashOne);
        $hashTwo = $this->getReflectionProperty($validatorTwo, 'hash');
        self::assertIsString($hashTwo);

        self::assertTrue($validatorOne->isValid($hashOne));
        self::assertFalse($validatorOne->isValid($hashTwo));
        self::assertFalse($validatorTwo->isValid($hashOne));
        self::assertTrue($validatorTwo->isValid($hashTwo));
    }

    #[IgnoreDeprecations]
    public function testCannotReValidateAnExpiredHash(): void
    {
        $hash = $this->getValidatorPropertyValue('hash');
        self::assertIsString($hash);

        self::assertTrue($this->validator->isValid($hash));
        $requestTime = $_SERVER['REQUEST_TIME'] ?? null;
        self::assertIsNumeric($requestTime);

        $container = $this->getValidatorPropertyValue('session');
        self::assertInstanceOf(Container::class, $container);

        $this->sessionManager->getStorage()->setMetadata(
            $container->getName(),
            ['EXPIRE' => $requestTime - 18600]
        );

        self::assertFalse($this->validator->isValid($hash));
    }

    public function testCanRejectArrayValues(): void
    {
        self::assertFalse($this->validator->isValid([]));
    }

    /**
     * @return string[][]
     * @psalm-return array<array-key, array{0: string}>
     */
    public static function fakeValuesDataProvider(): array
    {
        return [
            [''],
            ['-fakeTokenId'],
            ['fakeTokenId-fakeTokenId'],
            ['fakeTokenId-'],
            ['fakeTokenId'],
            [md5(uniqid()) . '-'],
            [md5(uniqid()) . '-' . md5(uniqid())],
            ['-' . md5(uniqid())],
        ];
    }

    #[IgnoreDeprecations]
    #[DataProvider('fakeValuesDataProvider')]
    public function testWithFakeValues(string $value): void
    {
        $validator = new Csrf();

        self::assertFalse($validator->isValid($value));
    }
}
