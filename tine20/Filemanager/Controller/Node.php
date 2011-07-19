<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use Tinebase_Model_Tree_Node_Path
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
            $_filter = new Tinebase_Model_Tree_Node_Filter();
        }
        
        $pathFilters = $_filter->getFilter('path', TRUE);
        
        // add base path and check grants
        foreach ($pathFilters as $key => $pathFilter) {
            $path = $pathFilter->getValue();
            $pathFilter->setValue($this->addBasePath($path));
            
            $container = $this->getContainer($path);
            if ($container) {
                $hasGrant = $this->_checkACLContainer($container, $_action);
                if (! $hasGrant) {
                    unset($pathFilters[$key]);
                }
            }
        }

        if (empty($pathFilters)) {
            throw new Tinebase_Exception_AccessDenied('Access denied.');
        }
    }
    
    /**
     * add base path
     * 
     * @param Tinebase_Model_Tree_Node_PathFilter $_pathFilter
     * @return string
     * 
     * @todo add /folders?
     */
    public function addBasePath($_path)
    {
        $basePath = $this->_backend->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
        
        $path = (strpos($_path, '/') === 0) ? $_path : '/' . $_path;
                
        return $basePath . $path;
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
     *   /shared(/containername(/*))
     *   /personal(/username(/containername(/*)))
     *   /otherUsers(/username(/containername(/*)))
     * 
     * @param string $_path
     * @return Tinebase_Model_Container
     * 
     * @deprecated functionality moved to Tinebase_Model_Tree_Node_Path
     */
    public function getContainer($_path)
    {
        // split path into parts
        $pathParts = explode('/', trim($_path, '/'), 4);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $container = NULL;
        
        if (!empty($pathParts[0])) {
            $containerType          = $pathParts[0];
            
            switch($containerType) {
                case Tinebase_Model_Container::TYPE_SHARED:
                    if (!empty($pathParts[1])) {
                        $container = $this->_searchContainerByName($pathParts[1], Tinebase_Model_Container::TYPE_SHARED);
                    }
                    
                    break;
                    
                case Tinebase_Model_Container::TYPE_PERSONAL:
                case Tinebase_Model_Container::TYPE_OTHERUSERS:
                    if (!empty($pathParts[1])) {
                        if ($containerType === Tinebase_Model_Container::TYPE_PERSONAL && $pathParts[1] !== Tinebase_Core::getUser()->accountLoginName) {
                            throw new Tinebase_Exception_NotFound('Invalid user name: ' . $pathParts[1] . '.');
                        }
                        
                        if (!empty($pathParts[2])) {
                            // explode again
                            $subPathParts = explode('/', $pathParts[2], 2);
                            $container = $this->_searchContainerByName($subPathParts[0], Tinebase_Model_Container::TYPE_PERSONAL);
                        }
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_NotFound('Invalid path: ' . $_path);
                    break;
            }
        }
        
        return $container;
    }
    
    /**
     * search container by name and type
     * 
     * @param string $_name
     * @param string $_type
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_NotFound
     * 
     * @deprecated functionality moved to Tinebase_Model_Tree_Node_Path
     */
    protected function _searchContainerByName($_name, $_type)
    {
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'name'           => $_name,
            'type'           => $_type,
        )));
        
        if (count($search) !== 1) {
            throw new Tinebase_Exception_NotFound('Container not found: ' . $_name);
        }
        
        return $search->getFirstRecord();
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
