<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to Sales WebDAV root node
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * app has personal folders
     *
     * @var string
     */
    protected $_hasPersonalFolders = false;
    
    /**
     * app has records folder
     *
     * @var string
     */
    protected $_hasRecordFolder = false;
    
    protected $_modules = array(
        'PurchaseInvoices'   => array(
            'right' => Sales_Acl_Rights::MANAGE_PURCHASE_INVOICES
        ),
        #'Offers'             => array(
        #    'right' => Sales_Acl_Rights::MANAGE_OFFERS
        #),
        #'OrderConfirmations' => array(
        #    'right' => Sales_Acl_Rights::MANAGE_ORDERCONFIRMATIONS
        #)
    );
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        if (!isset($this->_modules[$name]) || !$this->_hasModuleRight($this->_modules[$name]['right'])) {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }
        
        return new Sales_Frontend_WebDAV_Module($this->_path . '/' . $name);
    }
    
    
    /**
     * Returns an array with all the child nodes
     * 
     * the records subtree is not returned as child here. It's only available via getChild().
     *
     * @return \Sabre\DAV\INode[]
     */
    function getChildren()
    {
        $children = array();
        
        foreach ($this->_modules as $moduleName => $moduleSettings) {
            if ($this->_hasModuleRight($moduleSettings['right'])) {
                $children[] = $this->getChild($moduleName);
            }
        }
        
        return $children;
    }
    
    /**
     * check if user has given right
     * 
     * @param string $right the right to check
     * @return boolean
     */
    protected function _hasModuleRight($right)
    {
        return Tinebase_Acl_Roles::getInstance()->hasRight($this->_getApplication(), Tinebase_Core::getUser(), $right);
    }
}
