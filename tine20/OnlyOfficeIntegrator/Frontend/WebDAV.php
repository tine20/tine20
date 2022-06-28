<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 */
class OnlyOfficeIntegrator_Frontend_WebDAV extends Filemanager_Frontend_WebDAV
{
    /**
     * app has personal folders
     *
     * @var string
     */
    protected $_hasPersonalFolders = false;

    protected $_model = OnlyOfficeIntegrator_Model_Node::class;

    protected function _getSharedChildren()
    {
        if (Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED) {
            return parent::_getSharedChildren();
        }

        return [];
    }
}
