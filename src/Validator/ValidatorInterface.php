<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

/**
 * Session validator interface
 *
 * @template T
 */
interface ValidatorInterface
{
    /**
     * This method will be called at the beginning of
     * every session to determine if the current environment matches
     * that which was store in the setup() procedure.
     */
    public function isValid(): bool;

    /**
     * Get data from validator to be used for validation comparisons
     *
     * @return T
     */
    public function getData(): mixed;

    /**
     * Get validator name for use with storing validators between requests
     */
    public function getName(): string;
}
