<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * app has records folder
     *
     * @var string
     */
    protected $_hasRecordFolder = false;

    /**
     * container model name
     *
     * one of: Tinebase_Model_Container | Tinebase_Model_Tree_Node
     *
     * @var string
     */
    protected $_containerModel = 'Tinebase_Model_Tree_Node';

    protected $_model = Filemanager_Model_Node::class;

    /**
     * @return array
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _getOtherUsersChildren()
    {
        $children = array();
        foreach (Tinebase_FileSystem::getInstance()->getOtherUsers(Tinebase_Core::getUser(),
                $this->_getApplicationName(), [Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_SYNC]) as
                $node) {
            $name = $node->name;
            // we never use id as name!
            if (!$this->_useLoginAsFolderName()) {
                /** @var Tinebase_Model_FullUser $user */
                $user = Tinebase_User::getInstance()->getUserByLoginName($name, 'Tinebase_Model_FullUser');
                $name = $user->accountDisplayName;
            }
            $children[] = $this->getChild($name);
        }

        return $children;
    }
}
