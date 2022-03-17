<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Import class for importing (json) data from an imap folder on a mail account
 *
 * @package     Felamimail
 * @subpackage  Import
 */
class Felamimail_Import_Imap extends Tinebase_Import_Abstract
{
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account;

    /**
     * @var Tinebase_Record_RecordSet
     */
    protected Tinebase_Record_RecordSet $_messagesToImport;

    /**
     * @var Tinebase_Record_RecordSet
     */
    protected Tinebase_Record_RecordSet $_importedMessages;

    /**
     * @var Felamimail_Model_Folder
     */
    protected Felamimail_Model_Folder $_folder;

    /**
     * @var int
     */
    protected int $_maxNumberOfImportRecords = 10;

    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        $this->_options = array_merge($this->_options, [
            'folder' => 'INBOX',
            'host' => 'localhost',
            'user' => '',
            'password' => '',
            'ssl' => 'none',
            'port' => 143,
        ]);

        parent::__construct($_options);

        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs model in config.');
        }

        $this->_setController();
    }

    /**
     * do something before the import
     *
     * @param mixed $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        $this->_initAccount();
        $this->_updateCaches();
        $this->_getMessages();
        $this->_importedMessages = new Tinebase_Record_RecordSet(Felamimail_Model_Message::class);
    }

    /**
     * create/init mail account
     * - mail account type: Tinebase_EmailUser_Model_Account::TYPE_USER
     * - user: current user
     *
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _initAccount()
    {
        $this->_account = Felamimail_Controller_Account::getInstance()->create(new Felamimail_Model_Account([
            'name' => 'Felamimail Import Account',
            'email' => 'felamimailimport@' . $this->_options['host'],
            'type' => Tinebase_EmailUser_Model_Account::TYPE_USER,
            'user_id' => Tinebase_Core::getUser()->getId(),
            'host' => $this->_options['host'],
            'ssl' => $this->_options['ssl'],
            'port' => $this->_options['port'],
            'user' => $this->_options['user'],
            'password' => $this->_options['password'],
        ]));
    }

    protected function _updateCaches()
    {
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account);
        $this->_folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName(
            $this->_account, $this->_options['folder']);
        Felamimail_Controller_Cache_Message::getInstance()->updateCache($this->_folder, 60);
    }

    protected function _getMessages()
    {
        // TODO allow to add additional filter options? like checks for special headers?
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
            ['field' => 'folder_id', 'operator' => 'equals', 'value' => $this->_folder->getId()],
            ['field' => 'flags', 'operator' => 'notin', 'value' => [Zend_Mail_Storage::FLAG_SEEN]],
        ]);
        $this->_messagesToImport = Felamimail_Controller_Message::getInstance()->search($filter, new Tinebase_Model_Pagination([
            'start' => 0,
            'limit' => $this->_maxNumberOfImportRecords,
        ]));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
            __LINE__ . ' Found ' . count($this->_messagesToImport) . ' messages for import');
    }

    /**
     * get raw data of a single record
     *
     * @param  mixed $_resource
     * @return array|boolean|null
     */
    protected function _getRawData(&$_resource)
    {
        // get next unread message of mail account
        $message = $this->_messagesToImport->getFirstRecord();
        if (!$message) {
            return false;
        }
        $this->_messagesToImport->removeFirst();

        // get json data from message body
        $body = Felamimail_Controller_Message::getInstance()->getMessageBody($message, 0, Zend_Mime::TYPE_TEXT);
        // TODO add check for valid json?
        $content = Tinebase_Helper::jsonDecode($body);
        if (! empty($content)) {
            $this->_importedMessages->addRecord($message);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' .
                __LINE__ . ' No valid JSON body found in message ' . print_r($message->toArray(), true));
            return null;
        }
        return $content;
    }
    
    /**
     * do something after the import
     */
    protected function _afterImport()
    {
        // mark all imported messages as seen
        Felamimail_Controller_Message_Flags::getInstance()->addFlags(
            $this->_importedMessages, [Zend_Mail_Storage::FLAG_SEEN]);

        // remove no longer needed import account
        Felamimail_Controller_Account::getInstance()->delete([$this->_account->getId()]);
    }
}
