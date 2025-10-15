<?php // phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.MismatchingCaseSensitivity


declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\Id;
use LaminasTest\Session\TestAsset\TestCustomEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function ini_set;
use function session_id;
use function session_start;

use const PHP_VERSION_ID;

class IdTest extends TestCase
{
    /** @psalm-return iterable<string, array{0: int, 1: string, 2: bool, 3: bool}> */
    public static function id(): iterable
    {
        yield '4, valid' => [4, '0123456789abcdef', true, true];
        yield '4, invalid (out of the range)' => [4, '0123456789abcdefg', false, true];
        yield '4, invalid (uppercase characters)' => [4, '0123456789ABCDEF', false, true];
        yield '4, invalid (out of the range with a dot)' => [4, '0123456789ABCDEF.123', false, false];

        yield '5, valid' => [5, '0123456789abcdefghijklmnopqrstuv', true, true];
        yield '5, invalid (out of the range)' => [5, '0123456789abcdefghijklmnopqrstuvw', false, true];
        yield '5, invalid (uppercase characters)' => [5, '0123456789ABCDEFGHIJKLMNOPQRSTUV', false, true];
        yield '5, invalid (out of the range with a dot)' => [5, '0123456789ABCDEFGHIJKLMNOPQRSTUV.123', false, false];

        yield '6, valid' => [6, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-,', true, true];
        yield '6, invalid (out of the range)' => [6, '0123456789.abcdefghijklmnopqrstuvwxyz', false, false];
    }

    #[IgnoreDeprecations]
    #[DataProvider('id')]
    #[RunInSeparateProcess]
    public function testIsValidPhp(int $bitsPerCharacter, string $id, bool $isValidPhpPre84, bool $isValidPhp84): void
    {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);

        $validator = new Id(Environment::fromGlobals($_SERVER), new Environment(sessionId: $id));

        if (PHP_VERSION_ID >= 80400) {
            self::assertSame($isValidPhp84, $validator->isValid());
        } else {
            self::assertSame($isValidPhpPre84, $validator->isValid());
        }
    }

    #[IgnoreDeprecations]
    #[DataProvider('id')]
    #[RunInSeparateProcess]
    public function testIsValidPhpWithCustomEnvironment(int $bitsPerCharacter, string $id, bool $isValid): void
    {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);

        $validator = new Id(
            TestCustomEnvironment::fromGlobals($_SERVER),
            new TestCustomEnvironment(
                sessionId: $id,
                firstCustomProperty: 'fistCustomValue',
                secondCustomProperty: 'secondCustomValue'
            )
        );
        self::assertSame($isValid, $validator->isValid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializedWithSessionIdWhenIdIsNotPassed(): void
    {
        session_start();
        $sessionId = session_id();
        $id        = new Id(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));

        self::assertSame($sessionId, $id->initial->getSessionId());
    }

    public function testValidatorName(): void
    {
        $id = new Id(Environment::fromGlobals($_SERVER), Environment::fromGlobals($_SERVER));

        self::assertSame(Id::class, $id->getName());
    }
}
