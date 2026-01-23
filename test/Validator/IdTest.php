<?php // phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.MismatchingCaseSensitivity


declare(strict_types=1);

namespace LaminasTest\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\Id;
use LaminasTest\Session\TestAsset\TestCustomEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function ini_set;
use function session_id;
use function session_start;

final class IdTest extends TestCase
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

    #[DataProvider('id')]
    #[IgnoreDeprecations]
    #[RunInSeparateProcess]
    #[RequiresPhp('>=8.4')]
    public function testIsValidPhp84AndNewer(
        int $bitsPerCharacter,
        string $id,
        bool $isValidPhpPre84,
        bool $isValidPhp84
    ): void {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);
        $validator = new Id();
        $isValid   = null;
        try {
            $validator->validate(Environment::fromGlobals($_SERVER), new Environment(sessionId: $id));
            $isValid = true;
        } catch (SessionValidationFailedException $e) {
            $isValid = false;
        } finally {
            $this->assertSame($isValidPhp84, $isValid);
        }
    }

    #[DataProvider('id')]
    #[IgnoreDeprecations]
    #[RunInSeparateProcess]
    #[RequiresPhp('<8.4')]
    public function testIsValidPhpPre84(
        int $bitsPerCharacter,
        string $id,
        bool $isValidPhpPre84,
        bool $isValidPhp84
    ): void {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);
        $validator = new Id();
        $isValid   = null;
        try {
            $validator->validate(Environment::fromGlobals($_SERVER), new Environment(sessionId: $id));
            $isValid = true;
        } catch (SessionValidationFailedException $e) {
            $isValid = false;
        } finally {
            $this->assertSame($isValidPhpPre84, $isValid);
        }
    }

    #[DataProvider('id')]
    #[IgnoreDeprecations]
    #[RunInSeparateProcess]
    #[RequiresPhp('>=8.4')]
    public function testIsValidPhp84AndNewerWithCustomEnvironment(
        int $bitsPerCharacter,
        string $id,
        bool $isValidPhpPre84,
        bool $isValidPhp84
    ): void {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);
        $validator = new Id();
        try {
            $validator->validate(
                TestCustomEnvironment::fromGlobals($_SERVER),
                new TestCustomEnvironment(
                    sessionId: $id,
                    firstCustomProperty: 'fistCustomValue',
                    secondCustomProperty: 'secondCustomValue'
                )
            );
            $isValid = true;
        } catch (SessionValidationFailedException $e) {
            $isValid = false;
        } finally {
            $this->assertSame($isValidPhp84, $isValid);
        }
    }

    #[DataProvider('id')]
    #[IgnoreDeprecations]
    #[RunInSeparateProcess]
    #[RequiresPhp('<8.4')]
    public function testIsValidPhpPre84WithCustomEnvironment(
        int $bitsPerCharacter,
        string $id,
        bool $isValidPhpPre84,
        bool $isValidPhp84
    ): void {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);
        $validator = new Id();
        $isValid   = null;
        try {
            $validator->validate(
                TestCustomEnvironment::fromGlobals($_SERVER),
                new TestCustomEnvironment(
                    sessionId: $id,
                    firstCustomProperty: 'fistCustomValue',
                    secondCustomProperty: 'secondCustomValue'
                )
            );
            $isValid = true;
        } catch (SessionValidationFailedException $e) {
            $isValid = false;
        } finally {
            $this->assertSame($isValidPhpPre84, $isValid);
        }
    }

    #[RunInSeparateProcess]
    public function testInitializedWithSessionIdWhenIdIsNotPassed(): void
    {
        session_start();
        $sessionId  = session_id();
        $id         = new Id();
        $initialEnv = Environment::fromGlobals($_SERVER);
        $id->validate($initialEnv, Environment::fromGlobals($_SERVER));
        $this->assertSame($sessionId, $initialEnv->getSessionId());
    }
}
