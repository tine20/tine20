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
        'id'                => array('allowEmpty' => true ),
        'account_id'        => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'account_type'      => array('presence' => 'required', 'allowEmpty' => false, 'InArray' => array(
            'anyone', 
            'user', 
            'group',
        )),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'name'              => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'value'             => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),        
        'type'              => array('presence' => 'required', 'allowEmpty' => false, 'InArray' => array(
            self::TYPE_NORMAL, 
            self::TYPE_DEFAULT, 
            self::TYPE_FORCED,
        )),
    );
}
