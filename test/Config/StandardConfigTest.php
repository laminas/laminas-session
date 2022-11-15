<?php // phpcs:disable Squiz.Commenting.FunctionComment.WrongStyle

namespace LaminasTest\Session\Config;

use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

/**
 * @covers \Laminas\Session\Config\StandardConfig
 */
class StandardConfigTest extends TestCase
{
    /** @var StandardConfig */
    protected $config;

    protected function setUp(): void
    {
        $this->config = new StandardConfig();
    }

    // session.save_path

    public function testSetSavePathErrorsOnInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid save_path provided');
        $this->config->setSavePath(__DIR__ . '/foobarboguspath');
    }

    public function testSavePathIsMutable(): void
    {
        $this->config->setSavePath(__DIR__);
        self::assertEquals(__DIR__, $this->config->getSavePath());
    }

    // session.name

    public function testNameIsMutable(): void
    {
        $this->config->setName('FOOBAR');
        self::assertEquals('FOOBAR', $this->config->getName());
    }

    // session.save_handler

    public function testSaveHandlerIsMutable(): void
    {
        $this->config->setSaveHandler('user');
        self::assertEquals('user', $this->config->getSaveHandler());
    }

    // session.gc_probability

    public function testGcProbabilityIsMutable(): void
    {
        $this->config->setGcProbability(20);
        self::assertEquals(20, $this->config->getGcProbability());
    }

    public function testGcProbabilityCanBeSetToZero(): void
    {
        $this->config->setGcProbability(0);
        self::assertEquals(0, $this->config->getGcProbability());
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

    public function testGcDivisorIsMutable(): void
    {
        $this->config->setGcDivisor(20);
        self::assertEquals(20, $this->config->getGcDivisor());
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

    public function testGcMaxlifetimeIsMutable(): void
    {
        $this->config->setGcMaxlifetime(20);
        self::assertEquals(20, $this->config->getGcMaxlifetime());
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

    public function testSerializeHandlerIsMutable(): void
    {
        $value = extension_loaded('wddx') ? 'wddx' : 'php_binary';
        $this->config->setSerializeHandler($value);
        self::assertEquals($value, $this->config->getSerializeHandler());
    }

    // session.cookie_lifetime

    public function testCookieLifetimeIsMutable(): void
    {
        $this->config->setCookieLifetime(20);
        self::assertEquals(20, $this->config->getCookieLifetime());
    }

    public function testCookieLifetimeCanBeZero(): void
    {
        $this->config->setCookieLifetime(0);
        self::assertEquals(0, $this->config->getCookieLifetime());
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

    public function testCookiePathIsMutable(): void
    {
        $this->config->setCookiePath('/foo');
        self::assertEquals('/foo', $this->config->getCookiePath());
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

    // session.cookie_samesite

    public function testCookieSameSiteIsMutable()
    {
        $this->config->setCookieSameSite('Strict');
        $this->assertEquals('Strict', $this->config->getCookieSameSite());
    }

    // session.cookie_secure

    public function testCookieSecureIsMutable(): void
    {
        $this->config->setCookieSecure(true);
        self::assertEquals(true, $this->config->getCookieSecure());
    }

    // session.cookie_httponly

    public function testCookieHttpOnlyIsMutable(): void
    {
        $this->config->setCookieHttpOnly(true);
        self::assertEquals(true, $this->config->getCookieHttpOnly());
    }

    // session.use_cookies

    public function testUseCookiesIsMutable(): void
    {
        $this->config->setUseCookies(true);
        self::assertEquals(true, (bool) $this->config->getUseCookies());
    }

    // session.use_only_cookies

    public function testUseOnlyCookiesIsMutable(): void
    {
        $this->config->setUseOnlyCookies(true);
        self::assertEquals(true, (bool) $this->config->getUseOnlyCookies());
    }

    // session.referer_check

    public function testRefererCheckIsMutable(): void
    {
        $this->config->setRefererCheck('FOOBAR');
        self::assertEquals('FOOBAR', $this->config->getRefererCheck());
    }

    public function testRefererCheckMayBeEmpty(): void
    {
        $this->config->setRefererCheck('');
        self::assertEquals('', $this->config->getRefererCheck());
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
            ['nocache'],
            ['public'],
            ['private'],
            ['private_no_expire'],
        ];
    }

    /**
     * @dataProvider cacheLimiters
     */
    public function testCacheLimiterIsMutable(string $cacheLimiter): void
    {
        $this->config->setCacheLimiter($cacheLimiter);
        self::assertEquals($cacheLimiter, $this->config->getCacheLimiter());
    }

    // session.cache_expire

    public function testCacheExpireIsMutable(): void
    {
        $this->config->setCacheExpire(20);
        self::assertEquals(20, $this->config->getCacheExpire());
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

    public function testUseTransSidIsMutable(): void
    {
        $this->config->setUseTransSid(true);
        self::assertEquals(true, (bool) $this->config->getUseTransSid());
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

    public function testSidLengthIsMutable(): void
    {
        $this->config->setSidLength(40);
        self::assertEquals(40, $this->config->getSidLength());
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
    public function sidBitsPerCharacters(): array
    {
        return [
            [4],
            [5],
            [6],
        ];
    }

    /**
     * @dataProvider sidBitsPerCharacters
     */
    public function testSidBitsPerCharacterIsMutable(int $sidBitsPerCharacter): void
    {
        $this->config->setSidBitsPerCharacter($sidBitsPerCharacter);
        self::assertEquals($sidBitsPerCharacter, $this->config->getSidBitsPerCharacter());
    }

    public function testSettingInvalidSidBitsPerCharacterRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sid bits per character provided');
        $this->config->setSidBitsPerCharacter('foobar_bogus');
    }

    // url_rewriter.tags

    public function testUrlRewriterTagsIsMutable(): void
    {
        $this->config->setUrlRewriterTags('a=href,form=action');
        self::assertEquals('a=href,form=action', $this->config->getUrlRewriterTags());
    }

    // remember_me_seconds

    public function testRememberMeSecondsIsMutable(): void
    {
        $this->config->setRememberMeSeconds(20);
        self::assertEquals(20, $this->config->getRememberMeSeconds());
    }

    public function testSettingInvalidRememberMeSecondsRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid remember_me_seconds; must be numeric');
        $this->config->setRememberMeSeconds('foobar_bogus');
    }

    public function testSettingInvalidRememberMeSecondsRaisesException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid remember_me_seconds; must be a positive integer');
        $this->config->setRememberMeSeconds(-1);
    }

    // setOptions
    /**
     * @dataProvider optionsProvider
     */
    public function testSetOptionsTranslatesUnderscoreSeparatedKeys(string $option, string $getter, mixed $value): void
    {
        $options = [$option => $value];
        $this->config->setOptions($options);
        self::assertSame($value, $this->config->$getter());
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: string,
     *     2: mixed
     * }>
     */
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
            [
                'save_handler',
                'getSaveHandler',
                'user',
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
                'cookie_samesite',
                'getCookieSameSite',
                'Lax',
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
}
