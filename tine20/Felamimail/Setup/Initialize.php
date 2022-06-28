<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
    * array with user role rights
    *
    * @var array
    */
    static protected $_userRoleRights = [
        Tinebase_Acl_Rights::RUN,
        Tinebase_Acl_Rights::MAINSCREEN,
        Felamimail_Acl_Rights::MANAGE_ACCOUNTS,
        Felamimail_Acl_Rights::ADD_ACCOUNTS,
    ];

    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
            'model'             => 'Felamimail_Model_MessageFilter',
        );

        $myInboxPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Felamimail_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => 'All inboxes of my email accounts', // _("All inboxes of my email accounts")
            'filters'           => array(
                array('field' => 'path'    , 'operator' => 'in', 'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        ))));

        $myUnseenPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All unread mail', // _("All unread mail")
            'description'       => 'All unread mail of my email accounts', // _("All unread mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'notin', 'value' => Zend_Mail_Storage::FLAG_SEEN),
            )
        ))));

        $myHighlightedPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All highlighted mail', // _("All highlighted mail")
            'description'       => 'All highlighted mail of my email accounts', // _("All highlighted mail of my email accounts")
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_FLAGGED),
            )
        ))));

        $myDraftsPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        self::createEmailNotificationTemplatesFolder();
    }

    /**
     * create vacation templates folder
     */
    public static function createVacationTemplatesFolder()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Creating vacation template in vfs ...');

        try {
            $basePath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );

            $node = Tinebase_FileSystem::getInstance()->createAclNode($basePath . '/Vacation Templates');

            if (false === ($fh = Tinebase_FileSystem::getInstance()->fopen($basePath . '/Vacation Templates/vacation_template_test.tpl', 'w'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create vacation_template_test.tpl file');
                return;
            }

            fwrite($fh, <<<'vacation_template_test'
Ich bin vom {startDate-de_DE} bis zum {endDate-de_DE} im Urlaub. Bitte kontaktieren Sie
 {representation-n_fn-1} ({representation-email-1}) oder {representation-n_fn-2} ({representation-email-2}).

I am on vacation until {endDate-en_US}. Please contact {representation-n_fn-1}
({representation-email-1}) or {representation-n_fn-2} ({representation-email-2}) instead.

{owncontact-n_fn}
vacation_template_test
            );

            if (true !== Tinebase_FileSystem::getInstance()->fclose($fh)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create vacation_template_test.tpl file');
                return;
            }

            Felamimail_Config::getInstance()->set(Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID, $node->getId());
        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create vacation template folder: ' . $teb);
        }
    }

    /**
     * create email notification templates folder
     */
    public static function createEmailNotificationTemplatesFolder()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Creating email notification template in vfs ...');

        try {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );
            $node = Tinebase_FileSystem::getInstance()->createAclNode($basepath . '/Email Notification Templates');
            Felamimail_Config::getInstance()->set(Felamimail_Config::EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID, $node->getId());

            if (false === ($fh = Tinebase_FileSystem::getInstance()->fopen($basepath . '/Email Notification Templates/defaultForwarding.sieve', 'w'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create defaultForwarding.sieve file');
                return;
            }

            fwrite($fh, <<<'sieveFile'
require ["enotify", "variables", "copy", "body"];

if header :contains "Return-Path" "<>" {
    if body :raw :contains "X-Tine20-Type: Notification" {
        notify :message "there was a notification bounce"
              "mailto:ADMIN_BOUNCE_EMAIL";
    }
} elsif header :contains "X-Tine20-Type" "Notification" {
    redirect :copy "USER_EXTERNAL_EMAIL"; 
} else {
    if header :matches "Subject" "*" {
        set "subject" "${1}";
    }
    if header :matches "From" "*" {
        set "from" "${1}";
    }
    set :encodeurl "message" "TRANSLATE_SUBJECT${from}: ${subject}";
    
    notify :message "TRANSLATE_SUBJECT${from}: ${subject}"
              "mailto:USER_EXTERNAL_EMAIL?body=${message}";
}
sieveFile
            );

            if (true !== Tinebase_FileSystem::getInstance()->fclose($fh)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create defaultForwarding.sieve file');
                return;
            }

        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create email notification template folder: ' . $teb);
        }
    }

    /**
     * init record observers
     */
    protected function _initializeRecordObservers()
    {
        self::addDeleteNodeObserver();
    }

    public static function addDeleteNodeObserver()
    {
        $deleteNodeObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Tree_Node::class,
            'observable_identifier' => NULL,
            'observer_model'        => Felamimail_Model_MessageFileLocation::class,
            'observer_identifier'   => 'DeleteMessageFileLocation',
            'observed_event'        => Tinebase_Event_Observer_DeleteFileNode::class
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteNodeObserver);
    }
}
