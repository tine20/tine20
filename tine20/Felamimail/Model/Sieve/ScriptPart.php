<?php
/**
 * class to hold Sieve script parts
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold script parts
 *
 * @property    integer id
 * @property    string  account_id
 * @property    string  type
 * @property    string  name
 * @property    string  script
 * @property    array   requires
 *
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_ScriptPart extends Tinebase_Record_Abstract
{
    /** @var string */
    const XPROPS_REQUIRES = 'requires';

    /** @var string */
    const TYPE_NOTIFICATION = 'notification';

    /** @var string  */
    const TYPE_ADB_LIST = 'adblist';

    /** @var string  */
    const TYPE_AUTO_MOVE_NOTIFICATION = 'autoMoveNotification';

    /**
     * key in $_validators/$_properties array for the field which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'script'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'requires'              => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * @param string $_type
     * @param string $_name
     * @param string $_script
     * @return Felamimail_Model_Sieve_ScriptPart
     */
    public static function createFromString($_type, $_name, $_script)
    {
        $instance = new self(array(), true);
        $instance->type = $_type;
        $instance->name = $_name;
        if (preg_match('/^require\s+\[([^\]]+)\];/', $_script, $matches)) {
            $_script = str_replace($matches[0], '', $_script);
            $require = explode(',', $matches[1]);
            array_walk($require, function(&$val) {$val = trim($val);});
            $instance->requires = $require;
        }
        $instance->script = $_script;

        return $instance;
    }

    public function runConvertToData()
    {
        if (isset($this->_properties[self::XPROPS_REQUIRES]) && is_array($this->_properties[self::XPROPS_REQUIRES])) {
            if (count($this->_properties[self::XPROPS_REQUIRES]) > 0) {
                $this->_properties[self::XPROPS_REQUIRES] = json_encode($this->_properties[self::XPROPS_REQUIRES]);
            } else {
                $this->_properties[self::XPROPS_REQUIRES] = null;
            }
        }

        parent::runConvertToData();
    }
}
