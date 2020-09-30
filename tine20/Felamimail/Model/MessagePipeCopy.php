<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var Felamimail_Model_Message $_data */
        $targetFolder = $this->_config['target']['folder'];
        $accountId = isset($this->_config['target']['accountid']) ? 
            $this->_config['target']['accountid'] :
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT};
        
        $account = Felamimail_Controller_Account::getInstance()->get($accountId);

        $folder = Felamimail_Model_MessagePipeMove::getTargetFolder($account, $targetFolder);

        if (!isset($this->_config['wrap'])) {
            // keep original message
            Felamimail_Controller_Message_Move::getInstance()->moveMessages($_data, $folder, true);
        } else {
            if (!isset($this->_config['wrap']['to']) || !isset($this->_config['wrap']['subject'])) {
                throw new Exception('wrap config "to" or "subject" is not set');
            }
            
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            $this->_config['wrap']['subject'] = $translation->_($this->_config['wrap']['subject']);

            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' NOT IMPLEMENTED YET - this needs to be implemented - currently no message is sent to wrap address!'
            );

            // TODO implement me
            // Felamimail_Controller_Message_Send::getInstance()->copyMessageWithAttachment($_data, $this->_config['wrap'], $folder);
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
