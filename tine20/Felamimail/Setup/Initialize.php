<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Felamimail initialization
 * 
 * @package     Setup
 */
class Felamimail_Setup_Initialize extends Setup_Initialize
{
    /**
    * array with user role rights, overwrite this in your app to add more rights to user role
    *
    * @var array
    */
    protected $_userRoleRights = array(
        Tinebase_Acl_Rights::RUN,
        Felamimail_Acl_Rights::MANAGE_ACCOUNTS,
    );
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
            'model'             => 'Felamimail_Model_MessageFilter',
        );
        
        $myInboxPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Felamimail_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => 'All inboxes of my email accounts', // _("All inboxes of my email accounts")
            'filters'           => array(
                array('field' => 'path'    , 'operator' => 'in', 'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        ))));
        
        $myUnseenPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All unread mail', // _("All unread mail")
            'description'       => 'All unread mail of my email accounts', // _("All unread mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'notin', 'value' => Zend_Mail_Storage::FLAG_SEEN),
            )
        ))));

        $myHighlightedPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All Highlighted mail', // _("All highlighted mail")
            'description'       => 'All highlighted mail of my email accounts', // _("All highlighted mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_FLAGGED),
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
            'Felamimail', 
            'Vacation Templates', 
            Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID
        );
        try {
            Tinebase_FileSystem::getInstance()->createContainerNode($templateContainer);
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create vacation template folder: ' . $teb);
        }
    }
}
