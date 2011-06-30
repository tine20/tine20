<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Node controller for Filemanager
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller_Node extends Tinebase_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * Filesystem backend
     *
     * @var Tinebase_FileSystem
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Filemanager_Controller_Node
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_backend = Tinebase_FileSystem::getInstance();
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
     * @return Filemanager_Controller_Node
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller_Node();
        }
        
        return self::$_instance;
    }
    
    /**
     * search tree nodes
     * 
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string|optional $_action
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->_checkFilterACL($_filter, 'get');
        
        $result = $this->_backend->searchNodes($_filter, $_pagination);
        return $result;
    }
    
    /**
     * checks filter acl and adds base path
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($_filter === NULL) {
            $_filter = new Tinebase_Model_Tree_NodeFilter();
        }
        
        $pathFilters = $_filter->getFilter('path', TRUE);
        
        if (! $pathFilters) {
            // force a standard path filter as ACL filter
            $pathFilters = array($_filter->createFilter('path', 'equals', '/personal/' . $this->_currentAccount->accountLoginName));
            $_filter->addFilter($pathFilters[0]);
        }
        
        // add base path and check grants
        foreach ($pathFilters as $key => $pathFilter) {
            $path = $pathFilter->getValue();
            $container = $this->getContainer($path);
            $hasGrant = $this->_checkACLContainer($container, $_action);
            if (! $hasGrant) {
                unset($pathFilters[$key]);
            } else {
                $pathFilter->setValue($this->addBasePath($path));
                $pathFilter->setContainer($container);
            }
        }

        if (empty($pathFilters)) {
            throw new Tinebase_Exception_AccessDenied('Access denied.');
        }
    }
    
    /**
     * add base path
     * 
     * @param Tinebase_Model_Tree_NodePathFilter $_pathFilter
     * @return string
     * 
     * @todo add /folders?
     */
    public function addBasePath($_path)
    {
        $basePath = $this->_backend->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
                
        return $basePath . $_path;
    }
    
    /**
     * check if user has the permissions for the container
     * 
     * @param Tinebase_Model_Container $_container
     * @param string $_action get|update|...
     * @return boolean
     */
    protected function _checkACLContainer($_container, $_action = 'get')
    {
        if (! $_container) {
            return FALSE;
        }
        
        if (Tinebase_Container::getInstance()->hasGrant($this->_currentAccount, $_container, Tinebase_Model_Grants::GRANT_ADMIN)) {
            return TRUE;
        }
        
        switch ($_action) {
            case 'get':
                $requiredGrant = Tinebase_Model_Grants::GRANT_READ;
                break;
            case 'update':
                $requiredGrant = Tinebase_Model_Grants::GRANT_EDIT;
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
        
        return Tinebase_Container::getInstance()->hasGrant($this->_currentAccount, $_container, $requiredGrant);
    }
    
    /**
     * get container from path
     * 
     * path can be: 
     *   /shared/containername(/*)
     *   /personal/username/containername(/*)
     * 
     * @param string $_path
     * @return Tinebase_Model_Container
     * 
     * @todo replace getContainerByName!
     */
    public function getContainer($_path)
    {
        // split path into parts
        $pathParts = explode('/', trim($_path, '/'), 4);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $container = NULL;
        
        if (!empty($pathParts[0])) {
            $containerType          = strtolower($pathParts[0]);
            
            switch($containerType) {
                case Tinebase_Model_Container::TYPE_SHARED:
                    if (!empty($pathParts[1])) {
                        // @todo replace getContainerByName!
                        $container = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $pathParts[2], $containerType);
                    }
                    
                    break;
                    
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    if (!empty($pathParts[1])) {
                        if ($pathParts[1] != Tinebase_Core::getUser()->accountLoginName) {
                            throw new Tinebase_Exception_NotFound('Invalid user name: ' . $pathParts[1] . '.');
                        }
                        
                        if (!empty($pathParts[2])) {
                            // explode again
                            $subPathParts = explode('/', $pathParts[2], 2);
                            // @todo replace getContainerByName!
                            $container = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $subPathParts[0], $containerType);
                        }
                    } else {
                        throw new Tinebase_Exception_NotFound('User name missing.');
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_NotFound('Invalid path');
                    break;
            }
        }
        
        return $container;
    }
    

    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string|optional $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        throw new Tinebase_Exception_NotImplemented('searchCount not implemented yet');
    }
}
