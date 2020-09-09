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
 * Tine20 implementation for numberables
 *
 * @package     Tinebase
 * @subpackage  Numberable
 */
class Tinebase_Numberable extends Tinebase_Numberable_Abstract
{
    protected static $_baseConfiguration = array(
        self::TABLENAME        => 'numberable',
        self::NUMCOLUMN        => 'number',
        self::BUCKETCOLUMN     => 'bucket'
    );

    protected static $_numberableCache = array();

    /**
     * the constructor
     *
     * allowed numberableConfiguration:
     *  - stepsize (optional)
     *  - bucketkey (optional)
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
        parent::__construct(array_merge($_numberableConfiguration, self::$_baseConfiguration), $_dbAdapter, $_options);
    }

    /**
     * @param string $_class
     * @param string $_field
     * @param array $_config
     * @return Tinebase_Numberable_Abstract
     * @throws Tinebase_Exception_NotImplemented
     */
    public static function getNumberable($_class, $_field, array $_config)
    {
        if (!isset(self::$_numberableCache[$_class . '_#_' . $_field])) {
            if ($_config['type'] === 'numberableStr') {
                self::$_numberableCache[$_class . '_#_' . $_field] = new Tinebase_Numberable_String($_config['config']);
            } elseif($_config['type'] === 'numberableInt') {
                self::$_numberableCache[$_class . '_#_' . $_field] = new Tinebase_Numberable($_config['config']);
            } else {
                throw new Tinebase_Exception_NotImplemented('field type "' . $_config['type'] . '" is not known');
            }
        }
        return self::$_numberableCache[$_class . '_#_' . $_field];
    }

    public static function clearCache()
    {
        self::$_numberableCache = [];
    }
}