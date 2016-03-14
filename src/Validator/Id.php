<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Session\Validator;

/**
 * Session id validator
 */
class Id implements ValidatorInterface
{
    /**
     * Internal data.
     *
     * @var string
     */
    protected $data;

    /**
     * Constructor
     * get the current session id and store it in the session as 'valid data'
     *
     * @param null|string $data
     */
    public function __construct($data = null)
    {
        if (empty($data)) {
            $data = session_id();
        }

        $this->data = $data;
    }

    /**
     * isValid() - this method will determine if the current session id does not contain invalid characters.
     *
     * @return bool
     */
    public function isValid()
    {
        $id = $this->data;
        $saveHandler = ini_get('session.save_handler');
        if ($saveHandler == 'cluster') { // Zend Server SC, validate only after last dash
            $dashPos = strrpos($id, '-');
            if ($dashPos) {
                $id = substr($id, $dashPos + 1);
            }
        }

        $hashBitsPerChar = ini_get('session.hash_bits_per_character');
        if (!$hashBitsPerChar) {
            $hashBitsPerChar = 5; // the default value
        }

        switch ($hashBitsPerChar) {
            case 4:
                $pattern = '^[0-9a-f]*$';
                break;
            case 5:
                $pattern = '^[0-9a-v]*$';
                break;
            case 6:
                $pattern = '^[0-9a-zA-Z-,]*$';
                break;
        }

        return (bool)preg_match('#'.$pattern.'#', $id);
    }

    /**
     * Retrieve token for validating call
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return validator name
     *
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }
}
