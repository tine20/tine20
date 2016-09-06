<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * message file controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_File extends Felamimail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_File
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message_File
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Message_File();
        }
        
        return self::$_instance;
    }

    /**
     * file messages into Filemanager / MailFiler / ..,.
     *
     * @param Felamimail_Model_MessageFilter|Tinebase_Record_RecordSet $messages
     * @param string $targetApp
     * @param string $targetPath
     * @return integer|boolean
     */
    public function fileMessages($messages, $targetApp, $targetPath)
    {
        $result = false;
        if (Tinebase_Core::getUser()->hasRight($targetApp, Tinebase_Acl_Rights::RUN)) {
            if ($messages instanceof Tinebase_Model_Filter_FilterGroup) {
                $iterator = new Tinebase_Record_Iterator(array(
                    'iteratable' => $this,
                    'controller' => $this,
                    'filter'     => $messages,
                    'function'   => 'processFileIteration',
                ));
                $iterateResult = $iterator->iterate($targetApp, $targetPath);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Filed ' . $iterateResult['totalcount'] . ' message(s).');
                $result = $iterateResult['totalcount'];

            } else if ($messages instanceof Tinebase_Model_Filter_FilterGroup) {
                $messages = $this->_convertToRecordSet($_messages, TRUE);
                $result = $this->processMoveIteration($messages, $targetFolder);
            }

        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' User does not have RUN right for application');
        }

        return $result;
    }

    /**
     * file messages
     *
     * @param Tinebase_Record_RecordSet $messages
     * @param string $targetApp
     * @param string $targetPath
     */
    public function processFileIteration(Tinebase_Record_RecordSet $messages, $targetApp, $targetPath)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to file ' . count($messages) . ' messages to ' . $targetApp . '/' . $targetPath);

        foreach ($messages as $message) {
            $nodeController = Tinebase_Core::getApplicationInstance($targetApp . '_Model_Node');
            $nodeController->fileMessage($targetPath, $message);
        }

        if (Felamimail_Config::getInstance()->get(Felamimail_Config::DELETE_ARCHIVED_MAIL)) {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($messages, array(Zend_Mail_Storage::FLAG_DELETED));
        }
    }
}
