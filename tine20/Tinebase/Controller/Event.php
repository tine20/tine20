<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller abstract for applications with event handling
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Event extends Tinebase_Controller_Abstract implements Tinebase_Event_Interface
{
    /**
     * disable events on demand
     * 
     * @var mixed   false => no events filtered, true => all events filtered, array => disable only specific events
     */
    protected $_disabledEvents = false;
    
    /**
     * @see Tinebase_Event_Interface::handleEvent()
     * @param Tinebase_Event_Abstract $_eventObject
     */
    public function handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($this->_disabledEvents === true) {
            // nothing todo
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' events are disabled. do nothing'
            );
            return;
        }
        
        $this->_handleEvent($_eventObject);
    }
    
    /**
     * implement logic for each controller in this function
     * 
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        // do nothing
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Event_Interface::suspendEvents()
     */
    public function suspendEvents()
    {
        $this->_disabledEvents = true;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Event_Interface::resumeEvents()
     */
    public function resumeEvents()
    {
        $this->_disabledEvents = false;
    }

    /**
     * creates the initial (file/tree node) folder for new accounts and returns it. skips creation if node already exists.
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the account object
     * @param string $applicationName
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Tree_Node
     */
    public function createPersonalFileFolder($_account, $applicationName)
    {
        $account = (! $_account instanceof Tinebase_Model_User)
            ? Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_account)
            : $_account;
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $nodeName = sprintf($translation->_("%s's personal files"), $account->accountFullName);
        $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                $applicationName,
                Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            ) . '/' . $account->getId() . '/' . $nodeName;

        if (true === Tinebase_FileSystem::getInstance()->fileExists($path)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found existing personal folder: "' .
                $path . '"');
            $personalNode = Tinebase_FileSystem::getInstance()->stat($path);
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new personal folder: "' .
                $path . '"');
            $personalNode = Tinebase_FileSystem::getInstance()->createAclNode($path);
        }

        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($personalNode));

        return $container;
    }

    protected function _addDefaultPersonalGrantsToContainer($container,
                                                            $applicationName,
                                                            $grants,
                                                            $accountId,
                                                            $models = [],
                                                            $grantModel = Tinebase_Model_Grants::class,
                                                            $additionalGrants = [])
    {
        if (! $container
            || $container->type !== Tinebase_Model_Container::TYPE_PERSONAL
            || ! in_array($container->model, $models)
            || $container->application_id !== Tinebase_Application::getInstance()
                ->getApplicationByName($applicationName)->getId())
        {
            return null;
        }

        $container->xprops()['Tinebase']['Container']['GrantsModel'] = $grantModel;

        if ($grants instanceof Tinebase_Record_RecordSet) {
            $grantRecord = new $grantModel([
                'account_id' => $accountId,
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            ]);
            foreach ($grantModel::getAllGrants() as $grant) {
                $grantRecord->$grant = true;
            }
            $grants->addRecord($grantRecord);

            foreach ($additionalGrants as $grantRecord) {
                $grants->addRecord($grantRecord);
            }
        }
    }
}
