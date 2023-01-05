<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * felamimail model message pipe copy config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 */
class Felamimail_Model_MessagePipeCopy implements Tinebase_BL_ElementInterface, Tinebase_BL_ElementConfigInterface
{
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var Felamimail_Model_Message $message */
        $message = $_data;

        if (array_key_exists('local_directory', $this->_config['target'])) {

            // create directory if it does not exist
            $targetDir = $this->_config['target']['local_directory'];
            
            if (! is_dir($targetDir)) {
                mkdir($targetDir);
            }

            // put message as eml into local_directory (filename = MESSAGE-ID.eml)
            $tempFile = Felamimail_Controller_Message::getInstance()->putRawMessageIntoTempfile($message);
            $filename = $message->headers['message-id'] ?? $message->message_id;
            $filename = mb_substr($filename, 0, 128);
            $filename =  preg_replace("/[^\w\d@._-]|\.\./", "", $filename) . '.eml';

            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Copy file to temp path: '
                . $tempFile->path . ' -> ' . $targetDir . DIRECTORY_SEPARATOR . $filename);
            
            copy($tempFile->path, $targetDir . DIRECTORY_SEPARATOR . $filename);
        } else {
            $accountId = $this->_config['target']['accountid'] ??
                Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT};
            $account = Felamimail_Controller_Account::getInstance()->get($accountId);

            $folder = Felamimail_Model_MessagePipeMove::getTargetFolder($account, $this->_config['target']['folder']);
            Felamimail_Controller_Message_Move::getInstance()->moveMessages($message, $folder, true, false);
        }
    }

    public function getNewBLElement()
    {
        return $this;
    }

    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' should not be called');
    }
}
