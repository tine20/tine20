<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * device controller for ActiveSync
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
class ActiveSync_Controller_Device extends Tinebase_Controller_Record_Abstract
{
    /**
     * the salutation backend
     *
     * @var ActiveSync_Backend_Device
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller_Device
     */
    private static $_instance = NULL;

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'ActiveSync_Model_Device';
    
    /**
     * the singleton pattern
     *
     * @return ActiveSync_Controller_Device
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_Controller_Device();
        }
        
        return self::$_instance;
    }
            
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend         = new ActiveSync_Backend_Device();
        $this->_doContainerACLChecks = false;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        $hasGrant = false;
        
        if (Tinebase_Core::getUser()->hasRight('ActiveSync', Tinebase_Acl_Rights::ADMIN) || $_record->owner_id == Tinebase_Core::getUser()->getId()) {
            $hasGrant = true;
        }
                
        if ($hasGrant !== true) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $_action);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * set filter for different ActiveSync content types
     * 
     * @param unknown_type $_deviceId
     * @param unknown_type $_class
     * @param unknown_type $_filterId
     * 
     * @return ActiveSync_Model_Device
     * @throws ActiveSync_Exception
     */
    public function setDeviceContentFilter($_deviceId, $_class, $_filterId)
    {
        $device = $this->_backend->get($_deviceId);
        
        if($device->owner_id != Tinebase_Core::getUser()->getId()) {
            throw new Tinebase_Exception_AccessDenied('not owner of device ' . $_deviceId);
        }
        
        $filterId = empty($_filterId) ? null : $_filterId;
        
        switch($_class) {
            case Syncroton_Data_Factory::CLASS_CALENDAR:
                $device->calendarfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_CONTACTS:
                $device->contactsfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_EMAIL:
                $device->emailfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_TASKS:
                $device->tasksfilter_id = $filterId;
                break;
                
            default:
                throw new ActiveSync_Exception('unsupported class ' . $_class);
        }
        
        $device = $this->_backend->update($device);
        
        return $device;
    }

    /**
     * - loop devices that have the "monitorLastPing" flag
     * - send message to configured emails if lastPing before configured threshold (days)
     *
     * @return boolean
     */
    public function monitorDeviceLastPing()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(ActiveSync_Model_Device::class, [
            ['field' => 'monitor_lastping', 'operator' => 'equals', 'value' => true]
        ]);
        $devices = $this->search($filter);
        $outdatedDevices = 0;
        $notificationThreshold = Tinebase_DateTime::now()->subDay(
            ActiveSync_Config::getInstance()->get(ActiveSync_Config::LAST_PING_MONITORING_THRESHOLD_DAYS)
        );
        foreach ($devices as $device) {
            if ($device->lastping && $device->lastping->isEarlier($notificationThreshold)) {
                $outdatedDevices++;
                $this->_sendLastPingNotification($device);
            }
        }

        return true;
    }

    /**
     * @param ActiveSync_Model_Device $device
     */
    protected function _sendLastPingNotification(ActiveSync_Model_Device $device)
    {
        $recipients = ActiveSync_Config::getInstance()->get(ActiveSync_Config::LAST_PING_MONITORING_NOTIFICATION_EMAILS);
        if (! empty($recipients)) {
            $plain = 'Last ping of device outdated.';
            $owner = Tinebase_User::getInstance()->getFullUserById($device->owner_id);
            $plain .= "\n\n" . 'Owner: ' . $owner->accountDisplayName . ' (' . $owner->accountLoginName . ')';
            $plain .= "\n\n" . 'Device: ' . print_r($device->toArray(), true);
            $subject = 'Tine 2.0 ActiveSync ping outdated for device of user ' . $owner->accountLoginName;

            foreach ($recipients as $recipient) {
                $recipients = array(new Addressbook_Model_Contact(array('email' => $recipient), true));
                try {
                    Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), $recipients,
                        $subject, $plain);
                } catch (Exception $e) {
                    // skipping recipient
                    Tinebase_Exception::log($e);
                }
            }
        }

        if (ActiveSync_Config::getInstance()->{ActiveSync_Config::LAST_PING_MONITORING_NOTIFICATION_TO_USER}) {
            $translation = Tinebase_Translation::getTranslation('ActiveSync');
            $locale = $translation->getLocale();
            $twig = new Tinebase_Twig($locale, $translation);
            if (!isset($owner)) {
                $owner = Tinebase_User::getInstance()->getFullUserById($device->owner_id);
            }

            $textTemplate = $twig->load('ActiveSync/views/lastPingNotificationToUserEmail.twig');
            $subject = sprintf($translation->_('%1$s ActiveSync failure'),
                Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_TITLE});

            try {
                Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(),
                    [new Addressbook_Model_Contact(['email' => $owner->accountEmailAddress], true)],
                    $subject, $textTemplate->render([]));
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }
    }
}
