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
     * imap status: ok
     *
     */
    const IMAP_STATUS_OK = 'ok';
    
    /**
     * imap status: disconnected
     *
     */
    const IMAP_STATUS_DISCONNECT = 'disconnect';
    
    /**
     * cache status: empty
     *
     */
    const CACHE_STATUS_EMPTY = 'empty';
    
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
     * cache status: invalid
     *
     */
    const CACHE_STATUS_INVALID = 'invalid';
    
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
        'id'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'localname'              => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'globalname'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),  // global name is the path from root folder
        'parent'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),   // global name of parent folder
        'account_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'default'),
        'delimiter'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_selectable'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'has_children'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'recent'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'system_folder'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // imap values
        'imap_status'            => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::CACHE_STATUS_EMPTY, 
            'InArray' => array(
                self::IMAP_STATUS_OK,
                self::IMAP_STATUS_DISCONNECT,
            )
        ),
        'imap_uidnext'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'imap_uidvalidity'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'imap_totalcount'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'imap_timestamp'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // cache values 
        'cache_status'           => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::CACHE_STATUS_EMPTY, 
            'InArray' => array(
                self::CACHE_STATUS_EMPTY,
                self::CACHE_STATUS_COMPLETE, 
                self::CACHE_STATUS_INCOMPLETE, 
                self::CACHE_STATUS_UPDATING
            )
        ),
        'cache_uidnext'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'cache_uidvalidity'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_totalcount'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_recentcount'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_unreadcount'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_timestamp'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cache_job_lowestuid'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_job_startuid'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_job_actions_estimate' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_job_actions_done' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'cache_timestamp',
        'imap_timestamp',
    );
}
