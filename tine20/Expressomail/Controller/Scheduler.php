<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Fernando Alberto Reuter Wendt <fernando-alberto.wendt@serpro.gov.br>
 * @copyright   Copyright (c) 2015 SERPRO GmbH (https://www.serpro.gov.br)
 */

/**
 * Scheduler controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_Scheduler extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_Scheduler
     */
    private static $_instance = NULL;

    /**
     * @var Expressomail_Backend_Scheduler
     */
    protected $_backend;

    /**
     * default status for new record entry
     */
    private $_defaultStatus = 'PENDING';

    /**
     * default priority for new record entry
     */
    private $_defaultPriority = 5;

    /**
     * shared folder global name base identifier
     */
    private $_sharedFolderPattern = 'user/';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_backend = new Expressomail_Backend_Scheduler();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /*
     * abstracted method
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_Scheduler();
        }

        return self::$_instance;
    }

    /**
    * Add new scheduled entry
    *
    * @param   string $folder
    * @return  Tinebase_Record
    * @throws  Tinebase_Exception_Database
    * @throws  Tinebase_Exception
    */
    public function addNewScheduler($folder)
    {
        try{
            $usrAccount = $this->_inspectAccountBeforeSchedule();
            $usrMaildir = $this->_inspectFolderBeforeSchedule($usrAccount, $folder);

            if((!$usrAccount) || (is_null($usrMaildir))){
                throw new Tinebase_Exception("There are account inconsistences at your request, and it will not be scheduled.");
            }

            $newArrayData = array(
                'account_id'     => $usrAccount,
                'folder'         => $usrMaildir,
                'scheduler_time' => Tinebase_DateTime::now(),
                'status'         => $this->_defaultStatus,
                'priority'       => $this->_defaultPriority
            );

            $newSchedule = new Expressomail_Model_Scheduler($newArrayData);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' - The new mail dir export scheduler is ' . print_r($newSchedule, true));

            $alreadyExists = $this->_ifExists($newArrayData);

            if(!$alreadyExists){
                $result = $this->_backend->create($newSchedule);
                return $result;
            } else{
                throw new Tinebase_Exception("Your request for selected mail folder already exists! You must wait for your pendding request to be processed first.");
            }

        } catch (Tinebase_Exception_Backend_Database $ex) {
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . 'Failed to schedule a new mail dir export entry at database.' . print_r($ex, true));
            $result = array('status' => 'failure', 'success' => 'false', 'msg' => $ex->getMessage());
            return $result;
        }
    }

    /**
    * Check registry on database
    *
    * @param   array   $arrayData
    * @return  boolean
    * @throws  Tinebase_Exception_Database
    */
    private function _ifExists(array $_arrayData)
    {
        try{
            $checkFilter = $this->_backend->findOne(array($_arrayData['account_id'], $_arrayData['folder'], $_arrayData['status'], $_arrayData['priority']));
            if($checkFilter > 0){
                return true;
            } else{
                return false;
            }
        } catch (Tinebase_Exception_Backend_Database $ex) {
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . 'Failed to schedule a new mail dir export entry at database.' . print_r($ex->getMessage()));
            throw $ex->getMessage();
        }
    }

    /**
    * Check user account
    *
    * @throws  Tinebase_Exception $ex;
    * @return  Tinebase_Core      $_account
    */
    private function _inspectAccountBeforeSchedule()
    {
        try{
            $_account = Tinebase_Core::getUser()->getId();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' - Fetched Core User ID for mail dir scheduler: ' . print_r($_account, true));
            if($_account){
                return $_account;
            }
            else {
                Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' - Failed to get Tinebase user account id');
                throw new Tinebase_Exception("Failed to get core user account on system!");
            }
        } catch (Tinebase_Exception $ex){
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' - Fetched Core User ID for mail dir scheduler: ' . print_r($_account, true));
            throw new Tinebase_Exception("Failed to get user account on system");
        }
    }

    /**
    * Check folder
    *
    * @param   string $_usrAccountId;
    * @param   string $_folderId;
    * @return  string $_globalName;
    * @throws  Tinebase_Exception;
    *
    */
    private function _inspectFolderBeforeSchedule($_usrAccountId, $_folderId)
    {
        $_folder = Expressomail_Controller_Folder::getInstance()->get($_folderId);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' - Fetched FOLDER core object: ' . print_r($_folder, true));
        if($_folder){
            $_globalName = $_folder->globalname;

            if($this->_isSharedFolder($_globalName) !== false){
                throw new Tinebase_Exception("Export schedule is not allowed on shared folders!");
            }

            $_accountId = $_folder->account_id;
            $_checkAccount = $this->_checkFolderAccount($_usrAccountId, $_accountId);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '- Fetched mail directory global name for mail dir scheduler: ' . $_globalName);

            if($_checkAccount){
                return $_globalName;
            } else{
                Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' - Tine user account is different from Expressomail account!');
                throw new Tinebase_Exception("Detected account inconsistence on request operation!");
            }
        } else{
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' - Fetched mail directory for mail dir scheduler does not exist or is invalid: ' . print_r($_folder, true));
            throw new Tinebase_Exception("Specified folder does not exist at mail system!");
        }
    }

    /**
    * Check if folder is a shared one
    *
    * @param   string $_globalName
    * @return  bool
    */
    private function _isSharedFolder($_globalName)
    {
        $_match = strpos($_globalname, $this->_sharedFolderPattern);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '- Checking if folder global name "' . $_globalName . '" is shared: ' . $_match);
        return $_match;
    }

    /**
    * Check folder account
    *
    * @param   string $_usrAccountId
    * @param   string $_folderId
    * @return  bool
    */
    private function _checkFolderAccount($_usrAccountId, $_folderAccount)
    {
        $_expressomailId = Expressomail_Controller_Account::getInstance()->get($_folderAccount);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '- Fetched ACCOUNT for Expressomail account: ' . $_expressomailId);
        if($_expressomailId->user_id === $_usrAccountId){
            return true;
        }
        else{
            return false;
        }
    }
}