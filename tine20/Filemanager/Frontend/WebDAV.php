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
    const FM_REAL_WEBDAV_ROOT = 'fmRealWebdavRoot';

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
     * @var Tinebase_WebDav_Root
     */
    protected $_root = null;

    /**
     * contructor
     *
     * @param string $path          the current path
     * @param array  $options       options
     */
    public function __construct($path, $options = array())
    {
        parent::__construct($path, $options);

        if (isset($options[self::FM_REAL_WEBDAV_ROOT])) {
            $this->_root = $options[self::FM_REAL_WEBDAV_ROOT];
        }
    }

    /**
     * get path parts
     *
     * @return array
     */
    protected function _getPathParts()
    {
        if (!$this->_pathParts) {
            if (null !== $this->_root) {
                $this->_pathParts = ['Filemanager'];
            } else {
                $this->_pathParts = parent::_getPathParts();
                if (count($this->_pathParts) === 1 && $this->_pathParts !== ['Filemanager'] && $this->_pathParts !== ['webdav']) {
                    $this->_pathParts = ['Filemanager', $this->_pathParts[0]];
                }
            }
        }
        return $this->_pathParts;
    }

    /**
     * @param string $name
     * @return Tinebase_WebDav_Container_Abstract|Tinebase_WebDav_Collection_AbstractContainerTree|Tinebase_Frontend_WebDAV_RecordCollection
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        if (null !== $this->_root) {
            try {
                return $this->_root->getChild($name);
            } catch (\Sabre\DAV\Exception\NotFound $e) {}
        }

        return parent::getChild($name);
    }

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
