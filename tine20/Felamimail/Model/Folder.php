<?php
/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        rename unreadcount -> unseen
 */

/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 */
class Felamimail_Model_Folder extends Tinebase_Record_Abstract
{  
    /**
     * cache status: empty
     *
     */
    const CACHE_STATUS_EMPTY = 'empty';
    
    /**
     * cache status: pending
     *
     */
    const CACHE_STATUS_PENDING = 'pending';
    
    /**
     * cache status: complete
     *
     */
    const CACHE_STATUS_COMPLETE = 'complete';
    
    /**
     * cache status: updating
     *
     */
    const CACHE_STATUS_UPDATING = 'updating';
    
    /**
     * cache status: incomplete
     *
     */
    const CACHE_STATUS_INCOMPLETE = 'incomplete';
    
    /**
     * cache status: incomplete
     *
     */
    const CACHE_STATUS_DELETING = 'deleting';
    
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
        'localname'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'globalname'            => array(Zend_Filter_Input::ALLOW_EMPTY => false), // global name is the path from root folder
        'parent'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'default'),
        'delimiter'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_selectable'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'has_children'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'recent'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'system_folder'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'timestamp'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'totalcount'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // folder status
        'unreadcount'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'recentcount'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // cache/mailbox sync values 
        'uidnext'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'uidvalidity'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_status'          => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::CACHE_STATUS_EMPTY, 
            'InArray' => array(
                self::CACHE_STATUS_EMPTY,
                self::CACHE_STATUS_PENDING,
                self::CACHE_STATUS_COMPLETE, 
                self::CACHE_STATUS_INCOMPLETE, 
                self::CACHE_STATUS_UPDATING,
                self::CACHE_STATUS_DELETING
            )
        ),
        'cache_lowest_uid'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'timestamp',
    );
}
