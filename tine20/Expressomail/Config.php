<?php
/**
 * @package     Expressomail
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Expressomail config class
 * 
 * @package     Expressomail
 * @subpackage  Config
 */
class Expressomail_Config extends Tinebase_Config_Abstract
{
    /**
     * id of (filsystem) container for vacation templates
     * 
     * @var string
     */
    const VACATION_TEMPLATES_CONTAINER_ID = 'vacationTemplatesContainerId';
    
    /**
     * user can set custom vacation message
     * 
     * @var string
     */
    const VACATION_CUSTOM_MESSAGE_ALLOWED = 'vacationMessageCustomAllowed';

    /**
     * Max Results on email search
     * 
     * @var string
     */
    const IMAPSEARCHMAXRESULTS = 'imapSearchMaxResults';
    
    /**
     * Interval for automatic saving drafts
     *
     * @var string
     */
    const AUTOSAVEDRAFTSINTERVAL = 'autoSaveDraftsInterval';

    /**
     * Email to send reports of phishings
     *
     * @var string
     */
    const REPORTPHISHINGEMAIL = 'reportPhishingEmail';

    /**
     * id to use at cache and '_config' database table
     * @var string
     */
    const EXPRESSOMAIL_SETTINGS = 'expressomailSettings';

    /**
     * default number of concats to define the max number of contacts it will import. will be overwriten by the config
     * email->maxContactAddToUnknown if that is set
     *
     */
    const MAX_CONTACT_ADD_TO_UNKNOWN = 'maxContactAddToUnknown';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::VACATION_TEMPLATES_CONTAINER_ID => array(
        //_('Vacation Templates Container ID')
            'label'                 => 'Vacation Templates Container ID',
            'description'           => 'Vacation Templates Container ID',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::VACATION_CUSTOM_MESSAGE_ALLOWED => array(
        //_('Custom Vacation Message')
            'label'                 => 'Custom Vacation Message',
        // _('User is allowed to set custom vacation message for system account')
            'description'           => 'User is allowed to set custom vacation message for system account',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
            'default'               => 1,
        ),
        self::IMAPSEARCHMAXRESULTS => array(
            'label'                 => 'Imap Search Max Results',
            'description'           => 'Max results in a search messages operation',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => 1000,
        ),
        self::AUTOSAVEDRAFTSINTERVAL => array(
            'label'                 => 'Auto Save Drafts Interval',
            'description'           => 'Interval for automatic saving drafts',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => 15,
        ),
        self::REPORTPHISHINGEMAIL => array(
            'label'                 => 'Report Phishing Email',
            'description'           => 'Email to which to report phishing',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => '',
        ),
        self::MAX_CONTACT_ADD_TO_UNKNOWN => array(
            'label'                 => 'Unknown Contacts Maximum Import',
            'description'           => 'Maximum contacts to use Unknown Contacts import feature',
            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => 10,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Expressomail';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
    
    /**
     * Clean the cache of Expressomail settings.
     * (non-PHPdoc)
     * @see Tinebase_Config_Abstract::clearCache()
     */
    public function clearCache($appFilter = null)
    {
    	parent::clearCache();
	    $cache = Tinebase_Core::getCache();
	    $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(Expressomail_Config::EXPRESSOMAIL_SETTINGS));
    }
}
