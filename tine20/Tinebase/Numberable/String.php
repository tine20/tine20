<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Numberable
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * Tine20 String Numberable implementation
 *
 * @package     Tinebase
 * @subpackage  Numberable
 */
class Tinebase_Numberable_String extends Tinebase_Numberable
{
    const ZEROFILL     = 'zerofill';
    const PREFIX       = 'prefix';

    protected $_zerofill = 0;
    protected $_prefix = '';

    /**
     * the constructor
     *
     * allowed numberableConfiguration:
     *  - stepsize (optional)
     *  - shardkey (optional)
     *  - zerofill (optional)
     *  - prefix (optional)
     *
     *
     * allowed options:
     * see parent class
     *
     * @param array $_numberableConfiguration
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_numberableConfiguration, $_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_numberableConfiguration, $_dbAdapter, $_options);

        if (isset($_numberableConfiguration[self::PREFIX])) {
            $this->_prefix = $_numberableConfiguration[self::PREFIX];
        }

        if (isset($_numberableConfiguration[self::ZEROFILL])) {
            if (!is_int($_numberableConfiguration[self::ZEROFILL])) {
                throw new Tinebase_Exception_UnexpectedValue('found improper "' . self::ZEROFILL . '" configuration: (not a int) "' . $_numberableConfiguration[self::ZEROFILL] . '"');
            }
            $this->_zerofill = $_numberableConfiguration[self::ZEROFILL];
        }
    }

    /**
     * returns the next numberable
     *
     * @return string
     */
    public function getNext()
    {
        return $this->_prefix . str_pad('' . parent::getNext(), $this->_zerofill, '0', STR_PAD_LEFT);
    }

    /**
     * inserts a new numberable
     *
     * @param string $value
     * @return bool
     */
    public function insert($value)
    {
        return parent::insert($this->_cutStringConvertToInt($value));
    }

    /**
     * frees a numberable
     *
     * @param string $value
     * @return bool
     */
    public function free($value)
    {
        return parent::free($this->_cutStringConvertToInt($value));
    }

    /**
     * removes the configured prefix and leading zeros, etc.
     * performs a strict format check
     * returns the integer part of the numberable
     *
     * @param string $value
     * @return int
     */
    protected function _cutStringConvertToInt($value)
    {
        $_value = (string)$value;
        if (($len = strlen($this->_prefix)) > 0)
        {
            if (strpos($_value, $this->_prefix) !== 0) {
                throw new Tinebase_Exception_UnexpectedValue('prefix missing: "' . $this->_prefix . '"');
            }
            $_value = substr($_value, $len);
        }

        $_value = ltrim($_value, '0');
        $orgLen = strlen($_value);
        $trimLen = strlen($_value);
        if ($orgLen !== $trimLen) {
            if ($trimLen >= $this->_zerofill || $orgLen > $this->_zerofill) {
                throw new Tinebase_Exception_UnexpectedValue('improper format: to many leading zeros "' . $value .'"');
            }
            if ($orgLen < $this->_zerofill) {
                throw new Tinebase_Exception_UnexpectedValue('improper format: not enough leading zeros "' . $value .'"');
            }
            if (0 === $trimLen) {
                $_value = '0';
            }
        }

        $intValue = intval($_value);
        if ($_value !== '' . $intValue) {
            throw new Tinebase_Exception_UnexpectedValue('improper format: wrong prefix or non digit found where none should be "' . $value . '"');
        }

        return $intValue;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }
}