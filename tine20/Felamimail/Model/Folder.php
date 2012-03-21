<?php
/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        rename unreadcount -> unseen
 */

/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 * @subpackage  Model
 *
 * @property  string  account_id
 * @property  string  localname
 * @property  string  globalname
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
     * meta folder trash constant
     */
    const FOLDER_TRASH = '_trash_';
    
    /**
     * meta folder sent constant
     */
    const FOLDER_SENT = '_sent_';
    
    /**
     * meta folder drafts constant
     */
    const FOLDER_DRAFTS = '_drafts_';
    
    /**
     * meta folder templates constant
     */
    const FOLDER_TEMPLATES = '_templates_';
    
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
            Zend_Filter_Input::DEFAULT_VALUE => self::IMAP_STATUS_OK,
            array('InArray', array(self::IMAP_STATUS_OK, self::IMAP_STATUS_DISCONNECT)),
        ),
        'imap_uidvalidity'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'imap_totalcount'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'imap_timestamp'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // cache values 
        'cache_status'           => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            Zend_Filter_Input::DEFAULT_VALUE => self::CACHE_STATUS_EMPTY,
            array('InArray', array(
                self::CACHE_STATUS_EMPTY,
                self::CACHE_STATUS_COMPLETE, 
                self::CACHE_STATUS_INCOMPLETE, 
                self::CACHE_STATUS_UPDATING
            )),
        ),
        'cache_uidvalidity'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_totalcount'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_recentcount'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_unreadcount'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_timestamp'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cache_job_lowestuid'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'cache_job_startuid'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // estimated number of actions when updating cache
        'cache_job_actions_est'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // number of actions done when updating cache
        'cache_job_actions_done' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // quota information
        'quota_usage'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'quota_limit'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
    
    /**
     * encode foldername given by user (convert to UTF7-IMAP)
     * 
     * @param string $_folderName
     * @return string
     */
    public static function encodeFolderName($_folderName)
    {
        if (extension_loaded('mbstring')) {
            $result = mb_convert_encoding($_folderName, "UTF7-IMAP", "utf-8");
        } else if (extension_loaded('imap')) {
            $result = imap_utf7_encode(iconv('utf-8', 'ISO-8859-1', $_folderName));
        } else {
            // fallback
            $result = replaceSpecialChars($_folderName);
        }
                
        return $result;
    }
    
    /**
     * decode foldername given by IMAP server (convert from UTF7-IMAP to UTF8)
     * 
     * @param string $_folderName
     * @return string
     */
    public static function decodeFolderName($_folderName)
    {
        if (extension_loaded('mbstring')) {
            $result = mb_convert_encoding($_folderName, "utf-8", "UTF7-IMAP");
        } else if (extension_loaded('imap')) {
            $result = iconv('ISO-8859-1', 'utf-8', imap_utf7_decode($_folderName));
        } else {
            // fallback
            $result = replaceSpecialChars($_folderName);
        }
        
        return $result;
    }
    
    /**
     * extract localname and parent globalname
     * 
     * @param string $_folderName
     * @return array
     */
    public static function extractLocalnameAndParent($_folderName, $_delimiter)
    {
        $globalNameParts = explode($_delimiter, $_folderName);
        $localname = array_pop($globalNameParts);
        $parent = (count($globalNameParts) > 0) ? implode($_delimiter, $globalNameParts) : '';
        
        return array(
            'localname' => $localname,
            'parent'    => $parent,
        );
    }
}
