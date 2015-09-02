<?php
/**
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Expressomail initialization
 * 
 * @package     Setup
 */
class Expressomail_Setup_Initialize extends Setup_Initialize
{
    /**
    * array with user role rights, overwrite this in your app to add more rights to user role
    *
    * @var array
    */
    protected $_userRoleRights = array(
        Tinebase_Acl_Rights::RUN,
        Expressomail_Acl_Rights::MANAGE_ACCOUNTS,
    );
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Expressomail')->getId(),
            'model'             => 'Expressomail_Model_MessageFilter',
        );
        
        $myInboxPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Expressomail_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => 'All inboxes of my email accounts', // _("All inboxes of my email accounts")
            'filters'           => array(
                array('field' => 'path'    , 'operator' => 'in', 'value' => Expressomail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        ))));
        
        $myUnseenPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All unread mail from Inbox', // _("All unread mail")
            'description'       => 'All unread mail of my Inbox', // _("All unread mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'notin', 'value' => Zend_Mail_Storage::FLAG_SEEN),
                array('field' => 'path'    , 'operator' => 'in', 'value' => Expressomail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        ))));

        $myHighlightedPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All Highlighted mail from Inbox', // _("All highlighted mail")
            'description'       => 'All highlighted mail of my Inbox', // _("All highlighted mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_FLAGGED),
                array('field' => 'path'    , 'operator' => 'in', 'value' => Expressomail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        ))));

        $myDraftsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All drafts', // _("All drafts")
            'description'       => 'All mails with the draft flag', // _("All mails with the draft flag")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_DRAFT),
            )
        ))));
    }
    
    /**
     * init application folders
     */
    protected function _initializeFolders()
    {
        self::createVacationTemplatesFolder();
    }
    
    /**
     * create vacation templates folder
     */
    public static function createVacationTemplatesFolder()
    {
        $templateContainer = Tinebase_Container::getInstance()->createSystemContainer(
            'Expressomail', 
            'Vacation Templates', 
            Expressomail_Config::VACATION_TEMPLATES_CONTAINER_ID
        );
        try {
            Tinebase_FileSystem::getInstance()->createContainerNode($templateContainer);
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create vacation template folder: ' . $teb);
        }
    }

    /**
     * init config settings
     * - save default values at database
     * - based from the code of class Addressbook_Setup_Initialize
     * (non-PHPdoc) @see tine20/Addressbook/Setup/Initialize::setDefaultInternalAddressbook()
     */
    protected function _initializeConfig()
    {
    	$properties = Expressomail_Config::getProperties();
        $property_imapSearchMaxResults = $properties[Expressomail_Config::IMAPSEARCHMAXRESULTS];
        $default_value_imapSearchMaxResults = $property_imapSearchMaxResults['default'];
        $config = array(Expressomail_Config::IMAPSEARCHMAXRESULTS => $default_value_imapSearchMaxResults);
        $property_autoSaveDraftsInterval = $properties[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL];
        $default_value_autoSaveDraftsInterval = $property_autoSaveDraftsInterval['default'];
        $config[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL] = $default_value_autoSaveDraftsInterval;
        $property_reportPhishingEmail = $properties[Expressomail_Config::REPORTPHISHINGEMAIL];
        $default_value_reportPhishingEmail = $property_reportPhishingEmail['default'];
        $config[Expressomail_Config::REPORTPHISHINGEMAIL] = $default_value_reportPhishingEmail;
    	Expressomail_Controller::getInstance()->saveConfigSettings($config);
    }

}
