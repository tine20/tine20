<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        add 'grouping' property?
 */

/**
 * class Tinebase_Model_Preference
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Preference extends Tinebase_Record_Abstract 
{
    /**
     * normal user/group preference
     *
     */
    const TYPE_USER = 'user';
    
    /**
     * default preference for anyone who has no specific preference
     *
     */
    const TYPE_DEFAULT = 'default';

    /**
     * admin default preference
     *
     */
    const TYPE_ADMIN = 'admin';
    
    /**
     * admin forced preference (can not be changed by users)
     *
     */
    const TYPE_FORCED = 'forced';

    /**
     * default preference value
     *
     */
    const DEFAULT_VALUE = '_default_';
    
    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'account_id'        => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => '0'),
        'account_type'      => array('presence' => 'required', 'allowEmpty' => FALSE, array('InArray', array(
            Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
        ))),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'name'              => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'value'             => array('presence' => 'required', 'allowEmpty' => TRUE),
        'type'              => array('presence' => 'required', 'allowEmpty' => FALSE, array('InArray', array(
            self::TYPE_USER,        // user defined
            self::TYPE_DEFAULT,     // code default
            self::TYPE_ADMIN,       // admin default
            self::TYPE_FORCED,      // admin forced
        ))),
    // xml field with select options for this preference => only available in TYPE_DEFAULT prefs
        'options'            => array('allowEmpty' => TRUE),
    // don't allow to set this preference in admin mode
        'personal_only'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // don't allow user to change value
        'locked'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        // multiselection preference
        //'multiselect'        =>  array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => false),
        'uiconfig'        =>  array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'recordConfig'    =>  array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
    );

    /**
     * TODO remove when converted to ModelConfig
     * TODO generalize / support other models
     */
    public function runConvertToRecord()
    {
        // convert value property if necessary
        if (Tinebase_Helper::is_json($this->value)) {
            $value = Tinebase_Helper::jsonDecode($this->value);
            switch ($value['modelName']) {
                case 'Tinebase_Model_Container':
                    $containers = array();
                    foreach ($value['ids'] as $containerId) {
                        try {
                            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
                            // TODO should be converted to array by json frontend
                            $containers[] = $container->toArray();
                        } catch (Exception $e) {
                            // not found / no access / ...
                        }
                    }
                    $this->value = $containers;
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('model not supported');

            }
        } else {
            parent::runConvertToRecord();
        }
    }

    /**
     * TODO remove when converted to ModelConfig
     * TODO generalize / support other models
     */
    public function runConvertToData()
    {
        // convert value property if necessary
        if (is_array($this->value) && is_array($this->recordConfig) && isset($this->recordConfig['modelName'])) {
            $value = array(
                'modelName' => $this->recordConfig['modelName'],
                'ids' => array(),
            );
            foreach ($this->value as $record) {
                if (isset($record['id'])) {
                    $value['ids'][] = $record['id'];
                }
            }
            $this->value = json_encode($value);
        } else {
            parent::runConvertToData();
        }
    }
}
