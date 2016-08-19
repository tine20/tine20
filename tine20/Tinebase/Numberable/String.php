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
    const CONF_ZEROFILL     = 'zerofill';
    const CONF_PREFIX       = 'prefix';

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
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_numberableConfiguration, $_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_numberableConfiguration, $_dbAdapter, $_options);

        if (isset($_numberableConfiguration[self::CONF_PREFIX])) {
            $this->_prefix = $_numberableConfiguration[self::CONF_PREFIX];
        }

        if (isset($_numberableConfiguration[self::CONF_ZEROFILL])) {
            if (!is_int($_numberableConfiguration[self::CONF_ZEROFILL])) {
                throw new Tinebase_Exception_UnexpectedValue('found improper "' . self::CONF_ZEROFILL . '" configuration: (not a int) "' . $_numberableConfiguration[self::CONF_ZEROFILL] . '"');
            }
            $this->_zerofill = $_numberableConfiguration[self::CONF_ZEROFILL];
        }
    }

    public function getNext()
    {
        return $this->_prefix . str_pad('' . parent::getNext(), $this->_zerofill, '0', STR_PAD_LEFT);
    }

    public function insert($value)
    {
        $_value = $value;
        if (($len = strlen($this->_prefix)) > 0)
        {
            if (strpos($_value, $this->_prefix) !== 0) {
                throw new Tinebase_Exception_UnexpectedValue('prefix missing');
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
        if ($_value !== ''.$intValue) {
            throw new Tinebase_Exception_UnexpectedValue('improper format: wrong prefix or non digit found where none should be "' . $value . '"');
        }

        return parent::insert($intValue);
    }
}