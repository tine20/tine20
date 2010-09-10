<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add 'reload required' property?
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
    const TYPE_NORMAL = 'normal';
    
    /**
     * default preference for anyone who has no specific preference
     *
     */
    const TYPE_DEFAULT = 'default';

    /**
     * forced preference (can not be changed by users)
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
        'account_type'      => array('presence' => 'required', 'allowEmpty' => FALSE, 'InArray' => array(
            Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, 
            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, 
            Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
        )),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'name'              => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'value'             => array('presence' => 'required', 'allowEmpty' => TRUE),
        'type'              => array('presence' => 'required', 'allowEmpty' => FALSE, 'InArray' => array(
            self::TYPE_NORMAL, 
            self::TYPE_DEFAULT, 
            self::TYPE_FORCED,
        )),
    // xml field with select options for this preference => only available in TYPE_DEFAULT prefs
        'options'            => array('allowEmpty' => TRUE),
    // don't allow to set this preference in admin mode
        'personal_only'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    );
}
