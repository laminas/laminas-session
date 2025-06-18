<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

/**
 * Session validator interface
 *
 * @psalm-import-type OptionsArgument from RemoteAddr
 */
interface ValidatorInterface
{
    /** @param OptionsArgument $options */
    public function __construct(Environment $initial, Environment $current, array $options = []);

    /**
     * This method will be called at the beginning of
     * every session to determine if the current environment matches
     * that which was store in the setup() procedure.
     */
    public function isValid(): bool;

    /**
     * Get validator name for use with storing validators between requests
     */
    public function getName(): string;
}
