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
 * @todo        move account type constants to a better place because it is needed at multiple places
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
     * account type user
     *
     */
    const ACCOUNT_TYPE_USER = 'user';
    
    /**
     * account type group
     *
     */
    const ACCOUNT_TYPE_GROUP = 'group';
    
    /**
     * account type anyone
     *
     */
    const ACCOUNT_TYPE_ANYONE = 'anyone';
    
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
        'account_id'        => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'account_type'      => array('presence' => 'required', 'allowEmpty' => FALSE, 'InArray' => array(
            self::ACCOUNT_TYPE_ANYONE, 
            self::ACCOUNT_TYPE_USER, 
            self::ACCOUNT_TYPE_GROUP,
        )),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'name'              => array('presence' => 'required', 'allowEmpty' => FALSE, 'Alnum'),
        'value'             => array('presence' => 'required', 'allowEmpty' => FALSE),
        'type'              => array('presence' => 'required', 'allowEmpty' => FALSE, 'InArray' => array(
            self::TYPE_NORMAL, 
            self::TYPE_DEFAULT, 
            self::TYPE_FORCED,
        )),
        // xml field with select options for this preference => only available in TYPE_DEFAULT prefs
        'options'            => array('allowEmpty' => TRUE),        
    );
}
