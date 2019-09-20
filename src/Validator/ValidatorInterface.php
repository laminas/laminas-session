<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Session\Validator;

/**
 * Session validator interface
 */
interface ValidatorInterface
{
    /**
     * This method will be called at the beginning of
     * every session to determine if the current environment matches
     * that which was store in the setup() procedure.
     *
     * @return bool
     */
    public function isValid();

    /**
     * Get data from validator to be used for validation comparisons
     *
     * @return mixed
     */
    public function getData();

    /**
     * Get validator name for use with storing validators between requests
     *
     * @return string
     */
    public function getName();
}
