<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Validator;

use Generator;
use Laminas\Session\Validator\Id;
use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{
    /**
     * @return Generator
     */
    public function id()
    {
        yield '4, valid' => [4, '0123456789abcdef', true];
        yield '4, invalid (out of the range)' => [4, '0123456789abcdefg', false];
        yield '4, invalid (uppercase characters)' => [4, '0123456789ABCDEF', false];

        yield '5, valid' => [5, '0123456789abcdefghijklmnopqrstuv', true];
        yield '5, invalid (out of the range)' => [5, '0123456789abcdefghijklmnopqrstuvw', false];
        yield '5, invalid (uppercase characters)' => [5, '0123456789ABCDEFGHIJKLMNOPQRSTUV', false];

        yield '6, valid' => [6, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-,', true];
        yield '6, invalid (out of the range)' => [6, '0123456789.abcdefghijklmnopqrstuvwxyz', false];
    }

    /**
     * @requires PHP 7.1
     * @runInSeparateProcess
     *
     * @dataProvider id
     *
     * @param int    $bitsPerCharacter
     * @param string $id
     * @param bool   $isValid
     */
    public function testIsValidPhp71($bitsPerCharacter, $id, $isValid)
    {
        ini_set('session.sid_bits_per_character', $bitsPerCharacter);

        $validator = new Id($id);
        self::assertSame($isValid, $validator->isValid());
    }

    /**
     * @requires PHP < 7.1
     * @runInSeparateProcess
     *
     * @dataProvider id
     *
     * @param int    $bitsPerCharacter
     * @param string $id
     * @param bool   $isValid
     */
    public function testIsValidPhpPriorTo71($bitsPerCharacter, $id, $isValid)
    {
        ini_set('session.hash_bits_per_character', $bitsPerCharacter);

        $validator = new Id($id);
        self::assertSame($isValid, $validator->isValid());
    }

    public function testConstructorSetId()
    {
        $id = new Id('1234');

        self::assertSame('1234', $id->getData());
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializedWithSessionIdWhenIdIsNotPassed()
    {
        session_start();
        $sessionId = session_id();

        $id = new Id();

        self::assertSame($sessionId, $id->getData());
    }

    public function testValidatorName()
    {
        $id = new Id();

        self::assertSame(Id::class, $id->getName());
    }
}
