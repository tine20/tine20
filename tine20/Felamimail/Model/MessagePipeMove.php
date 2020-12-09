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
 * felamimail model message pipe move config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 */
class Felamimail_Model_MessagePipeMove implements Tinebase_BL_ElementInterface, Tinebase_BL_ElementConfigInterface
{
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * move mail to trash, use configured trash folder of current user
     *
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     * @return void
     * @throws Tinebase_Exception_NotFound
     * @throws Exception
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var Felamimail_Model_Message $_data */

        $targetFolder = $this->_config['target']['folder'];
        $account = Felamimail_Controller_Account::getInstance()->get($_data->account_id);
        $folder = self::getTargetFolder($account, $targetFolder);

        Felamimail_Controller_Message_Move::getInstance()->moveMessages($_data, $folder, false);
    }

    /**
     * @param Felamimail_Model_Account $_account
     * @param string $_targetFolder
     * @return Felamimail_Model_Folder
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @throws Tinebase_Exception_SystemGeneric
     */
    public static function getTargetFolder($_account, $_targetFolder)
    {
        if(!$_account) {
            throw new Exception("account is not set");
        }
        
        if ( !is_string($_targetFolder)) {
            throw new Exception("target folder needs to be a string");
        }
        
        if (empty($_targetFolder)) {
            throw new Exception("config target folder is not set");
        }

        if ($_targetFolder[0] == '#') {
            $folderName = strtolower(substr($_targetFolder, 1));
            $propertyName = "{$folderName}_folder";
            if (!$_account->has($propertyName)) {
                throw new Exception("config target folder is not set");
            }
            $_targetFolder = $_account->{$propertyName};
        }

        try {
            $_targetFolder = str_replace('/', $_account->delimiter, $_targetFolder);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' looking for folder ' . $_targetFolder);
            $folder = Felamimail_Controller_Folder::getInstance()
                ->getByBackendAndGlobalName($_account->getId(), $_targetFolder);
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Folder not found: ' . $_targetFolder);
            $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_targetFolder, $_account->delimiter);
            
            $parentSubs = Felamimail_Controller_Cache_Folder::getInstance()
                ->update($_account, $splitFolderName['parent'], TRUE);
            $folder = $parentSubs->filter('globalname', $_targetFolder)->getFirstRecord();
            
            if ($folder === NULL) {
                $folder = Felamimail_Controller_Folder::getInstance()
                    ->create($_account->getId(), $splitFolderName['localname'], $splitFolderName['parent']);
            }
        }

        return $folder;
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
