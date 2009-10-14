<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_Config
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Config extends Tinebase_Record_Abstract 
{   
    /**
     * imap conf name
     * 
     * @var string
     */
    const IMAP = 'imap';
    
    /**
     * smtp conf name
     * 
     * @var string
     */
    const SMTP = 'smtp';

    /**
     * authentication backend config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKEND = 'Tinebase_Authentication_BackendConfiguration';
    
    /**
     * authentication backend type config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKENDTYPE = 'Tinebase_Authentication_BackendType';
    
    /**
     * save automatic alarms when creating new record
     * 
     * @var string
     */
    const AUTOMATICALARM = 'automaticalarm';
    
    /**
     * user backend config
     * 
     * @var string
     */
    const USERBACKEND = 'Tinebase_User_BackendConfiguration';
    
    /**
     * user backend type config
     * 
     * @var string
     */
    const USERBACKENDTYPE = 'Tinebase_User_BackendType';
    
    /**
     * cronjob user id
     * 
     * @var string
     */
    const CRONUSERID = 'cronuserid';
    
    /**
     * user defined page title postfix for browser page title
     * 
     * @var string
     */
    const PAGETITLEPOSTFIX = 'pagetitlepostfix';

    /**
     * ods export config
     * 
     * @var string
     */
    const ODSEXPORTCONFIG = 'odsexportconfig';
    
    /**
     * app defaults
     * 
     * @var string
     */
    const APPDEFAULTS = 'appdefaults';
    
    /**
     * logout redirect url
     * 
     * @var string
     */
    const LOGOUTREDIRECTURL = 'logoutRedirectUrl';
    
    /**
     * identifier
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
        'application_id'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'name'              => array('presence' => 'required', 'allowEmpty' => false ),
        'value'             => array('presence' => 'required', 'allowEmpty' => false ),        
    );
    
} // end of Tinebase_Model_Config
