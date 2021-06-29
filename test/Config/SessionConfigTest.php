<?php // phpcs:disable Squiz.Commenting.FunctionComment.WrongStyle

namespace LaminasTest\Session\Config;

use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Exception;
use Laminas\Session\Exception\InvalidArgumentException;
use LaminasTest\Session\TestAsset\TestSaveHandler;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SessionHandlerInterface;
use stdClass;

use function array_merge;
use function extension_loaded;
use function hash_algos;
use function ini_get;
use function session_start;
use function var_export;

/**
 * @runTestsInSeparateProcesses
 * @covers \Laminas\Session\Config\SessionConfig
 */
class SessionConfigTest extends TestCase
{
    use PHPMock;

    /** @var SessionConfig */
    protected $config;

    protected function setUp(): void
    {
        SessionConfig::$phpinfo           = 'phpinfo';
        SessionConfig::$sessionModuleName = 'session_module_name';
        $this->config                     = new SessionConfig();
    }

    protected function tearDown(): void
    {
        $this->config                     = null;
        SessionConfig::$phpinfo           = 'phpinfo';
        SessionConfig::$sessionModuleName = 'session_module_name';
    }

    // session.save_path

    public function testSetSavePathErrorsOnInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid save_path provided');
        $this->config->setSavePath(__DIR__ . '/foobarboguspath');
    }

    public function testSavePathDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.save_path'), $this->config->getSavePath());
    }

    public function testSavePathIsMutable(): void
    {
        $this->config->setSavePath(__DIR__);
        self::assertEquals(__DIR__, $this->config->getSavePath());
    }

    public function testSavePathAltersIniSetting(): void
    {
        $this->config->setSavePath(__DIR__);
        self::assertEquals(__DIR__, ini_get('session.save_path'));
    }

    public function testSavePathCanBeNonDirectoryWhenSaveHandlerNotFiles(): void
    {
        $this->config->setPhpSaveHandler(TestSaveHandler::class);
        $this->config->setSavePath('/tmp/sessions.db');
        self::assertEquals('/tmp/sessions.db', ini_get('session.save_path'));
    }

    // session.name

    public function testNameDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.name'), $this->config->getName());
    }

    public function testNameIsMutable(): void
    {
        $this->config->setName('FOOBAR');
        self::assertEquals('FOOBAR', $this->config->getName());
    }

    public function testNameAltersIniSetting(): void
    {
        $this->config->setName('FOOBAR');
        self::assertEquals('FOOBAR', ini_get('session.name'));
    }

    public function testNameAltersIniSettingAfterSessionStart(): void
    {
        session_start();

        $this->config->setName('FOOBAR');
        self::assertEquals('FOOBAR', ini_get('session.name'));
    }

    public function testIdempotentNameAltersIniSettingWithSameValueAfterSessionStart(): void
    {
        $this->config->setName('FOOBAR');
        session_start();

        $this->config->setName('FOOBAR');
        self::assertEquals('FOOBAR', ini_get('session.name'));
    }

    // session.save_handler

    public function testSaveHandlerDefaultsToIniSettings(): void
    {
        self::assertSame(
            ini_get('session.save_handler'),
            $this->config->getSaveHandler(),
            var_export($this->config->toArray(), 1)
        );
    }

    public function testSaveHandlerIsMutable(): void
    {
        $this->config->setSaveHandler(TestSaveHandler::class);
        self::assertSame(TestSaveHandler::class, $this->config->getSaveHandler());
    }

    public function testSaveHandlerDoesNotAlterIniSetting(): void
    {
        $this->config->setSaveHandler(TestSaveHandler::class);
        self::assertNotSame(TestSaveHandler::class, ini_get('session.save_handler'));
    }

    public function testSettingInvalidSaveHandlerRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid save handler specified');
        $this->config->setPhpSaveHandler('foobar_bogus');
    }

    // session.gc_probability

    public function testGcProbabilityDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.gc_probability'), $this->config->getGcProbability());
    }

    public function testGcProbabilityIsMutable(): void
    {
        $this->config->setGcProbability(20);
        self::assertEquals(20, $this->config->getGcProbability());
    }

    public function testGcProbabilityAltersIniSetting(): void
    {
        $this->config->setGcProbability(24);
        self::assertEquals(24, ini_get('session.gc_probability'));
    }

    public function testSettingInvalidGcProbabilityRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_probability; must be numeric');
        $this->config->setGcProbability('foobar_bogus');
    }

    public function testSettingInvalidGcProbabilityRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_probability; must be a percentage');
        $this->config->setGcProbability(-1);
    }

    public function testSettingInvalidGcProbabilityRaisesException3(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_probability; must be a percentage');
        $this->config->setGcProbability(101);
    }

    // session.gc_divisor

    public function testGcDivisorDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.gc_divisor'), $this->config->getGcDivisor());
    }

    public function testGcDivisorIsMutable(): void
    {
        $this->config->setGcDivisor(20);
        self::assertEquals(20, $this->config->getGcDivisor());
    }

    public function testGcDivisorAltersIniSetting(): void
    {
        $this->config->setGcDivisor(24);
        self::assertEquals(24, ini_get('session.gc_divisor'));
    }

    public function testSettingInvalidGcDivisorRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_divisor; must be numeric');
        $this->config->setGcDivisor('foobar_bogus');
    }

    public function testSettingInvalidGcDivisorRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_divisor; must be a positive integer');
        $this->config->setGcDivisor(-1);
    }

    // session.gc_maxlifetime

    public function testGcMaxlifetimeDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.gc_maxlifetime'), $this->config->getGcMaxlifetime());
    }

    public function testGcMaxlifetimeIsMutable(): void
    {
        $this->config->setGcMaxlifetime(20);
        self::assertEquals(20, $this->config->getGcMaxlifetime());
    }

    public function testGcMaxlifetimeAltersIniSetting(): void
    {
        $this->config->setGcMaxlifetime(24);
        self::assertEquals(24, ini_get('session.gc_maxlifetime'));
    }

    public function testSettingInvalidGcMaxlifetimeRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_maxlifetime; must be numeric');
        $this->config->setGcMaxlifetime('foobar_bogus');
    }

    public function testSettingInvalidGcMaxlifetimeRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gc_maxlifetime; must be a positive integer');
        $this->config->setGcMaxlifetime(-1);
    }

    // session.serialize_handler

    public function testSerializeHandlerDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.serialize_handler'), $this->config->getSerializeHandler());
    }

    public function testSerializeHandlerIsMutable(): void
    {
        $value = extension_loaded('wddx') ? 'wddx' : 'php_binary';
        $this->config->setSerializeHandler($value);
        self::assertEquals($value, $this->config->getSerializeHandler());
    }

    public function testSerializeHandlerAltersIniSetting(): void
    {
        $value = extension_loaded('wddx') ? 'wddx' : 'php_binary';
        $this->config->setSerializeHandler($value);
        self::assertEquals($value, ini_get('session.serialize_handler'));
    }

    public function testSettingInvalidSerializeHandlerRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid serialize handler specified');
        $this->config->setSerializeHandler('foobar_bogus');
    }

    // session.cookie_lifetime

    public function testCookieLifetimeDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cookie_lifetime'), $this->config->getCookieLifetime());
    }

    public function testCookieLifetimeIsMutable(): void
    {
        $this->config->setCookieLifetime(20);
        self::assertEquals(20, $this->config->getCookieLifetime());
    }

    public function testCookieLifetimeAltersIniSetting(): void
    {
        $this->config->setCookieLifetime(24);
        self::assertEquals(24, ini_get('session.cookie_lifetime'));
    }

    public function testCookieLifetimeCanBeZero(): void
    {
        $this->config->setCookieLifetime(0);
        self::assertEquals(0, ini_get('session.cookie_lifetime'));
    }

    public function testSettingInvalidCookieLifetimeRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie_lifetime; must be numeric');
        $this->config->setCookieLifetime('foobar_bogus');
    }

    public function testSettingInvalidCookieLifetimeRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie_lifetime; must be a positive integer or zero');
        $this->config->setCookieLifetime(-1);
    }

    // session.cookie_path

    public function testCookiePathDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cookie_path'), $this->config->getCookiePath());
    }

    public function testCookiePathIsMutable(): void
    {
        $this->config->setCookiePath('/foo');
        self::assertEquals('/foo', $this->config->getCookiePath());
    }

    public function testCookiePathAltersIniSetting(): void
    {
        $this->config->setCookiePath('/bar');
        self::assertEquals('/bar', ini_get('session.cookie_path'));
    }

    public function testSettingInvalidCookiePathRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie path');
        $this->config->setCookiePath(24);
    }

    public function testSettingInvalidCookiePathRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie path');
        $this->config->setCookiePath('foo');
    }

    public function testSettingInvalidCookiePathRaisesException3(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie path');
        $this->config->setCookiePath('D:\\WINDOWS\\System32\\drivers\\etc\\hosts');
    }

    // session.cookie_domain

    public function testCookieDomainDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cookie_domain'), $this->config->getCookieDomain());
    }

    public function testCookieDomainIsMutable(): void
    {
        $this->config->setCookieDomain('example.com');
        self::assertEquals('example.com', $this->config->getCookieDomain());
    }

    public function testCookieDomainCanBeEmpty(): void
    {
        $this->config->setCookieDomain('');
        self::assertEquals('', $this->config->getCookieDomain());
    }

    public function testCookieDomainAltersIniSetting(): void
    {
        $this->config->setCookieDomain('localhost');
        self::assertEquals('localhost', ini_get('session.cookie_domain'));
    }

    public function testSettingInvalidCookieDomainRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie domain: must be a string');
        $this->config->setCookieDomain(24);
    }

    public function testSettingInvalidCookieDomainRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match the expected structure for a DNS hostname');
        $this->config->setCookieDomain('D:\\WINDOWS\\System32\\drivers\\etc\\hosts');
    }

    // session.cookie_secure

    public function testCookieSecureDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cookie_secure'), $this->config->getCookieSecure());
    }

    public function testCookieSecureIsMutable(): void
    {
        $value = ! ini_get('session.cookie_secure');
        $this->config->setCookieSecure($value);
        self::assertEquals($value, $this->config->getCookieSecure());
    }

    public function testCookieSecureAltersIniSetting(): void
    {
        $value = ! ini_get('session.cookie_secure');
        $this->config->setCookieSecure($value);
        self::assertEquals($value, ini_get('session.cookie_secure'));
    }

    // session.cookie_httponly

    public function testCookieHttpOnlyDefaultsToIniSettings(): void
    {
        self::assertSame((bool) ini_get('session.cookie_httponly'), $this->config->getCookieHttpOnly());
    }

    public function testCookieHttpOnlyIsMutable(): void
    {
        $value = ! ini_get('session.cookie_httponly');
        $this->config->setCookieHttpOnly($value);
        self::assertEquals($value, $this->config->getCookieHttpOnly());
    }

    public function testCookieHttpOnlyAltersIniSetting(): void
    {
        $value = ! ini_get('session.cookie_httponly');
        $this->config->setCookieHttpOnly($value);
        self::assertEquals($value, ini_get('session.cookie_httponly'));
    }

    // session.use_cookies

    public function testUseCookiesDefaultsToIniSettings(): void
    {
        self::assertSame((bool) ini_get('session.use_cookies'), $this->config->getUseCookies());
    }

    public function testUseCookiesIsMutable(): void
    {
        $value = ! ini_get('session.use_cookies');
        $this->config->setUseCookies($value);
        self::assertEquals($value, $this->config->getUseCookies());
    }

    public function testUseCookiesAltersIniSetting(): void
    {
        $value = ! ini_get('session.use_cookies');
        $this->config->setUseCookies($value);
        self::assertEquals($value, (bool) ini_get('session.use_cookies'));
    }

    // session.use_only_cookies

    public function testUseOnlyCookiesDefaultsToIniSettings(): void
    {
        self::assertSame((bool) ini_get('session.use_only_cookies'), $this->config->getUseOnlyCookies());
    }

    public function testUseOnlyCookiesIsMutable(): void
    {
        $value = ! ini_get('session.use_only_cookies');
        $this->config->setOption('use_only_cookies', $value);
        self::assertEquals($value, (bool) $this->config->getOption('use_only_cookies'));
    }

    public function testUseOnlyCookiesAltersIniSetting(): void
    {
        $value = ! ini_get('session.use_only_cookies');
        $this->config->setOption('use_only_cookies', $value);
        self::assertEquals($value, (bool) ini_get('session.use_only_cookies'));
    }

    // session.referer_check

    public function testRefererCheckDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.referer_check'), $this->config->getRefererCheck());
    }

    public function testRefererCheckIsMutable(): void
    {
        $this->config->setOption('referer_check', 'FOOBAR');
        self::assertEquals('FOOBAR', $this->config->getOption('referer_check'));
    }

    public function testRefererCheckMayBeEmpty(): void
    {
        $this->config->setOption('referer_check', '');
        self::assertEquals('', $this->config->getOption('referer_check'));
    }

    public function testRefererCheckAltersIniSetting(): void
    {
        $this->config->setOption('referer_check', 'BARBAZ');
        self::assertEquals('BARBAZ', ini_get('session.referer_check'));
    }

    public function testSetEntropyFileError(): void
    {
        $this->expectDeprecation();
        $this->config->getEntropyFile();
    }

    public function testGetEntropyFileError(): void
    {
        $this->expectDeprecation();
        $this->config->setEntropyFile(__FILE__);
    }

    // session.entropy_length

    public function testGetEntropyLengthError(): void
    {
        $this->expectDeprecation();
        $this->config->getEntropyLength();
    }

    public function testSetEntropyLengthError(): void
    {
        $this->expectDeprecation();
        $this->config->setEntropyLength(0);
    }

    // session.cache_limiter

    /** @psalm-return array<array-key, array{0: string}> */
    public function cacheLimiters(): array
    {
        return [
            [''],
            ['nocache'],
            ['public'],
            ['private'],
            ['private_no_expire'],
        ];
    }

    public function testCacheLimiterDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cache_limiter'), $this->config->getCacheLimiter());
    }

    /**
     * @dataProvider cacheLimiters
     */
    public function testCacheLimiterIsMutable(string $cacheLimiter): void
    {
        $this->config->setCacheLimiter($cacheLimiter);
        self::assertEquals($cacheLimiter, $this->config->getCacheLimiter());
    }

    /**
     * @dataProvider cacheLimiters
     */
    public function testCacheLimiterAltersIniSetting(string $cacheLimiter): void
    {
        $this->config->setCacheLimiter($cacheLimiter);
        self::assertEquals($cacheLimiter, ini_get('session.cache_limiter'));
    }

    public function testSettingInvalidCacheLimiterRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache limiter provided');
        $this->config->setCacheLimiter('foobar_bogus');
    }

    // session.cache_expire

    public function testCacheExpireDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.cache_expire'), $this->config->getCacheExpire());
    }

    public function testCacheExpireIsMutable(): void
    {
        $this->config->setCacheExpire(20);
        self::assertEquals(20, $this->config->getCacheExpire());
    }

    public function testCacheExpireAltersIniSetting(): void
    {
        $this->config->setCacheExpire(24);
        self::assertEquals(24, ini_get('session.cache_expire'));
    }

    public function testSettingInvalidCacheExpireRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache_expire; must be numeric');
        $this->config->setCacheExpire('foobar_bogus');
    }

    public function testSettingInvalidCacheExpireRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache_expire; must be a positive integer');
        $this->config->setCacheExpire(-1);
    }

    // session.use_trans_sid

    public function testUseTransSidDefaultsToIniSettings(): void
    {
        self::assertSame((bool) ini_get('session.use_trans_sid'), $this->config->getUseTransSid());
    }

    public function testUseTransSidIsMutable(): void
    {
        $value = ! ini_get('session.use_trans_sid');
        $this->config->setOption('use_trans_sid', $value);
        self::assertEquals($value, (bool) $this->config->getOption('use_trans_sid'));
    }

    public function testUseTransSidAltersIniSetting(): void
    {
        $value = ! ini_get('session.use_trans_sid');
        $this->config->setOption('use_trans_sid', $value);
        self::assertEquals($value, (bool) ini_get('session.use_trans_sid'));
    }

    // session.hash_function

    public function hashFunctions(): array
    {
        $hashFunctions = array_merge([0, 1], hash_algos());
        $provider      = [];
        foreach ($hashFunctions as $function) {
            $provider[] = [$function];
        }
        return $provider;
    }

    public function testGetHashFunctionError(): void
    {
        $this->expectDeprecation();
        $this->config->getHashFunction();
    }

    public function testSetHashFunctionError(): void
    {
        $this->expectDeprecation();
        $this->config->setHashFunction('foobar_bogus');
    }

    // session.hash_bits_per_character

    public function hashBitsPerCharacters(): array
    {
        return [
            [4],
            [5],
            [6],
        ];
    }

    public function testGetHashBitsPerCharacterError(): void
    {
        $this->expectDeprecation();
        $this->config->getHashBitsPerCharacter();
    }

    public function testSetHashBitsPerCharacterError(): void
    {
        $this->expectDeprecation();
        $this->config->setHashBitsPerCharacter(5);
    }

    // session.sid_length

    public function testSidLengthDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.sid_length'), $this->config->getSidLength());
    }

    public function testSidLengthIsMutable(): void
    {
        $this->config->setSidLength(40);
        self::assertEquals(40, $this->config->getSidLength());
    }

    public function testSidLengthAltersIniSetting(): void
    {
        $this->config->setSidLength(40);
        self::assertEquals(40, ini_get('session.sid_length'));
    }

    public function testSettingInvalidSidLengthRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid length provided');
        $this->config->setSidLength('foobar_bogus');
    }

    public function testSettingOutOfRangeSidLengthRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid length provided');
        $this->config->setSidLength(999);
    }

    // session.sid_bits_per_character

    /** @psalm-return array<array-key, array{0: int}> */
    public function sidSidPerCharacters(): array
    {
        return [
            [4],
            [5],
            [6],
        ];
    }

    public function testSidBitsPerCharacterDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('session.sid_bits_per_character'), $this->config->getSidBitsPerCharacter());
    }

    /**
     * @dataProvider sidSidPerCharacters
     */
    public function testSidBitsPerCharacterIsMutable(int $sidBitsPerCharacter): void
    {
        $this->config->setSidBitsPerCharacter($sidBitsPerCharacter);
        self::assertEquals($sidBitsPerCharacter, $this->config->getSidBitsPerCharacter());
    }

    /**
     * @dataProvider sidSidPerCharacters
     */
    public function testSidBitsPerCharacterAltersIniSetting(int $sidBitsPerCharacter): void
    {
        $this->config->setSidBitsPerCharacter($sidBitsPerCharacter);
        self::assertEquals($sidBitsPerCharacter, ini_get('session.sid_bits_per_character'));
    }

    public function testSettingInvalidSidBitsPerCharacterRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sid bits per character provided');
        $this->config->setSidBitsPerCharacter('foobar_bogus');
    }

    public function testSettingOutOfBoundSidBitsPerCharacterRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sid bits per character provided');
        $this->config->setSidBitsPerCharacter(999);
    }

    // url_rewriter.tags

    public function testUrlRewriterTagsDefaultsToIniSettings(): void
    {
        self::assertSame(ini_get('url_rewriter.tags'), $this->config->getUrlRewriterTags());
    }

    public function testUrlRewriterTagsIsMutable(): void
    {
        $this->config->setUrlRewriterTags('a=href,form=action');
        self::assertEquals('a=href,form=action', $this->config->getUrlRewriterTags());
    }

    public function testUrlRewriterTagsAltersIniSetting(): void
    {
        $this->config->setUrlRewriterTags('a=href,fieldset=');
        self::assertEquals('a=href,fieldset=', ini_get('url_rewriter.tags'));
    }

    // remember_me_seconds

    public function testRememberMeSecondsDefaultsToTwoWeeks(): void
    {
        self::assertEquals(1209600, $this->config->getRememberMeSeconds());
    }

    public function testRememberMeSecondsIsMutable(): void
    {
        $this->config->setRememberMeSeconds(604800);
        self::assertEquals(604800, $this->config->getRememberMeSeconds());
    }

    // setOption

    /**
     * @dataProvider optionsProvider
     * @param mixed $value
     */
    public function testSetOptionSetsIniSetting(string $option, string $getter, $value): void
    {
        // Leaving out special cases.
        if ($option === 'remember_me_seconds' || $option === 'url_rewriter_tags') {
            self::markTestSkipped('remember_me_seconds && url_rewriter_tags');
        }

        $this->config->setStorageOption($option, $value);
        self::assertEquals(ini_get('session.' . $option), $value);
    }

    public function testSetOptionUrlRewriterTagsGetsMunged(): void
    {
        $value = 'a=href';
        $this->config->setStorageOption('url_rewriter_tags', $value);
        self::assertEquals(ini_get('url_rewriter.tags'), $value);
    }

    public function testSetOptionRememberMeSecondsDoesNothing(): void
    {
        self::markTestIncomplete('I have no idea how to test this.');
    }

    public function testSetOptionsThrowsExceptionOnInvalidKey(): void
    {
        $badKey = 'snarfblat';
        $value  = 'foobar';

        $this->expectException('InvalidArgumentException');
        $this->config->setStorageOption($badKey, $value);
    }

    // setOptions

    /**
     * @dataProvider optionsProvider
     * @param mixed $value
     */
    public function testSetOptionsTranslatesUnderscoreSeparatedKeys(
        string $option,
        string $getter,
        $value
    ): void {
        $options = [$option => $value];
        $this->config->setOptions($options);
        if ('getOption' === $getter) {
            self::assertSame($value, $this->config->getOption($option));
        } else {
            self::assertSame($value, $this->config->$getter());
        }
    }

    /** @psalm-return array<array-key, array{0: string, 1: string, 2: mixed}> */
    public function optionsProvider(): array
    {
        return [
            [
                'save_path',
                'getSavePath',
                __DIR__,
            ],
            [
                'name',
                'getName',
                'FOOBAR',
            ],
            'UserDefinedSaveHandler' => [
                'save_handler',
                'getOption',
                'files',
            ],
            [
                'gc_probability',
                'getGcProbability',
                42,
            ],
            [
                'gc_divisor',
                'getGcDivisor',
                3,
            ],
            [
                'gc_maxlifetime',
                'getGcMaxlifetime',
                180,
            ],
            [
                'serialize_handler',
                'getSerializeHandler',
                'php_binary',
            ],
            [
                'cookie_lifetime',
                'getCookieLifetime',
                180,
            ],
            [
                'cookie_path',
                'getCookiePath',
                '/foo/bar',
            ],
            [
                'cookie_domain',
                'getCookieDomain',
                'getlaminas.org',
            ],
            [
                'cookie_secure',
                'getCookieSecure',
                true,
            ],
            [
                'cookie_httponly',
                'getCookieHttpOnly',
                true,
            ],
            [
                'use_cookies',
                'getUseCookies',
                false,
            ],
            [
                'use_only_cookies',
                'getUseOnlyCookies',
                true,
            ],
            [
                'referer_check',
                'getRefererCheck',
                'foobar',
            ],
            [
                'cache_limiter',
                'getCacheLimiter',
                'private',
            ],
            [
                'cache_expire',
                'getCacheExpire',
                42,
            ],
            [
                'use_trans_sid',
                'getUseTransSid',
                true,
            ],
            [
                'url_rewriter_tags',
                'getUrlRewriterTags',
                'a=href',
            ],
            [
                'sid_length',
                'getSidLength',
                40,
            ],
            [
                'sid_bits_per_character',
                'getSidBitsPerCharacter',
                5,
            ],
        ];
    }

    public function testSetPhpSaveHandlerRaisesExceptionForAttemptsToSetUserModule(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid save handler specified ("user")');
        $this->config->setPhpSaveHandler('user');
    }

    public function testErrorSettingKnownSaveHandlerResultsInException(): void
    {
        $r = new ReflectionProperty($this->config, 'knownSaveHandlers');
        $r->setAccessible(true);
        $r->setValue($this->config, ['files', 'notreallyredis']);

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error setting session save handler module "notreallyredis"');
        $this->config->setPhpSaveHandler('notreallyredis');
    }

    public function testProvidingNonSessionHandlerToSetPhpSaveHandlerResultsInException(): void
    {
        $handler = new stdClass();

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('("stdClass"); must implement SessionHandlerInterface');
        $this->config->setPhpSaveHandler($handler);
    }

    public function testProvidingValidKnownSessionHandlerToSetPhpSaveHandlerResultsInNoErrors(): void
    {
        /** @return string */
        $this->config::$phpinfo = function () {
            echo "Registered save handlers => user files unittest";
        };

        /** @return bool|string */
        $this->config::$sessionModuleName = function (?string $module = null) {
            static $moduleName;

            if ($module !== null) {
                $moduleName = $module;
            }

            return $moduleName;
        };

        self::assertSame($this->config, $this->config->setPhpSaveHandler('unittest'));
        self::assertEquals('unittest', $this->config->getOption('save_handler'));
    }

    public function testCanProvidePathWhenUsingRedisSaveHandler(): void
    {
        $this->config::$phpinfo = function () {
            echo "Registered save handlers => user files redis";
        };

        /** @return bool|string */
        $this->config::$sessionModuleName = function (?string $module = null) {
            static $moduleName;

            if ($module !== null) {
                $moduleName = $module;
            }

            return $moduleName;
        };

        $url                              = 'tcp://localhost:6379?auth=foobar&database=1';

        $this->config->setOption('save_handler', 'redis');
        $this->config->setOption('save_path', $url);

        self::assertSame($url, $this->config->getOption('save_path'));
    }

    public function testNotCallLocateRegisteredSaveHandlersMethodIfSessionHandlerInterfaceWasPassed(): void
    {
        $spy                    = new stdClass();
        $spy->seen              = false;
        $this->config::$phpinfo = function () use ($spy): void {
            $spy->seen = true;
        };

        $saveHandler = $this->createMock(SessionHandlerInterface::class);
        $this->config->setPhpSaveHandler($saveHandler);
        self::assertFalse($spy->seen, 'phpinfo was called and should not have been');
    }
}
