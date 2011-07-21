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
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($pathFilter->getValue()));
            $pathFilter->setValue($path);
            
            if ($path->container) {
                $hasGrant = $this->_checkACLContainer($path->container, $_action);
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

    /**
     * create node(s)
     * 
     * @param string|array $filenames
     * @param string $type directory or file
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function createNodes($_filenames, $_type)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        
        foreach ($_filenames as $filename) {
            $node = $this->_createNode($filename, $_type);
            $result->addRecord($node);
        }
        
        return $result;
    }
    
    /**
     * create new node
     * 
     * @param string $_flatpath
     * @param string $_type
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _createNode($_flatpath, $_type)
    {
        if (! in_array($_type, array(Tinebase_Model_Tree_Node::TYPE_FILE, Tinebase_Model_Tree_Node::TYPE_FOLDER))) {
            throw new Tinebase_Exception_InvalidArgument('Type ' . $_type . 'not supported.');
        } 

        list($parentPathRecord, $newNodeName) = Tinebase_Model_Tree_Node_Path::getParentAndChild($this->addBasePath($_flatpath));
        $this->_checkPathACL($parentPathRecord, 'update');
        $newNodePath = $parentPathRecord . '/' . $newNodeName;
        
        if (! $parentPathRecord->container && Tinebase_Model_Tree_Node::TYPE_FOLDER) {
            $container = $this->_createContainer($newNodeName, $parentPathRecord->containerType);
            $newNodePath = $parentPathRecord . '/' . $container->getId();
        } else {
            $container = NULL;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Creating new path ' . $newNodePath . ' of type ' . $_type);
        
        $this->_backend->mkDir($newNodePath);
        
        $newNode = $this->_backend->stat($newNodePath);
        $this->resolveContainerAndAddPath($newNode, $parentPathRecord, $container);
        
        return $newNode;
    }
    
    /**
     * check acl of path
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_action
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkPathACL(Tinebase_Model_Tree_Node_Path $_path, $_action = 'get')
    {
        $hasPermission = FALSE;
        
        if ($_path->container) {
            $hasPermission = $this->_checkACLContainer($path->container, $_action);
        } else {
            switch ($_path->containerType) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    $hasPermission = ($_path->containerOwner === $this->_currentAccount->accountLoginName);
                    break;
                case Tinebase_Model_Container::TYPE_SHARED:
                    $hasPermission = $this->checkRight(Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS, FALSE);
                    break;
            }
        }
        
        if (! $hasPermission) {
            throw new Tinebase_Exception_AccessDenied('No permission to ' . $_action . ' in path ' . $_path->flatpath);
        }
    }
    
    /**
     * create new container
     * 
     * @param string $_name
     * @param string $_type
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _createContainer($_name, $_type)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => $app->getId(),
            'name'           => $_name,
            'type'           => $_type,
        )));
        if (count($search) > 0) {
            throw new Tinebase_Exception_Record_NotAllowed('Container ' . $_name . ' of type ' . $_type . ' already exists.');
        }
        
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $_name,
            'type'           => $_type,
            'backend'        => 'sql',
            'application_id' => $app->getId(),
        )));
        
        return $container;
    }

    /**
     * resolve node container and path
     * 
     * (1) add path to records 
     * (2) replace name with container record, if node name is a container id 
     *     / path is toplevel (shared/personal with useraccount
     * 
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param Tinebase_Model_Container $_container
     */
    public function resolveContainerAndAddPath($_records, Tinebase_Model_Tree_Node_Path $_path, Tinebase_Model_Container $_container = NULL)
    {
        $records = ($_records instanceof Tinebase_Model_Tree_Node) 
            ? new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($_records)) : $_records;
        
        if (! $_path->container) {
            // fetch top level container nodes
            if ($_container === NULL) {
                $containerIds = $_records->name;
                $containers = Tinebase_Container::getInstance()->getMultiple($containerIds);
            } else {
                $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($_container));
            }
        }
        
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $flatpathWithoutBasepath = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_path->flatpath, $app);
        
        foreach ($records as $record) {
            $record->path = $flatpathWithoutBasepath . '/' . $record->name;
            if (! $_path->container) {
                $idx = $containers->getIndexById($record->name);
                if ($idx !== FALSE) {
                    $record->name = $containers[$idx];
                }
            }
        }
    }
}
