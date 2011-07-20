<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class representing one node path
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @property    string                      containerType
 * @property    string                      flatpath
 * @property    string                      statpath
 * @property    Tinebase_Model_Application  application
 * @property    Tinebase_Model_Container    container
 * @property    Tinebase_Model_FullUser     user
 * 
 * exploded flat path should look like this:
 * 
 * [0] => app id [required]
 * [1] => type [required]
 * [2] => container | accountLoginName
 * [3] => container | directory
 * [4] => directory
 * [5] => directory
 * [...]
 */
class Tinebase_Model_Tree_Node_Path extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'flatpath';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array (
        'containerType'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'containerOwner'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'flatpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'statpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'			    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * create new path record from given path string
     * 
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node_Path
     */
    public static function createFromPath($_path)
    {
        $path = ($_path instanceof Tinebase_Model_Tree_Node_Path) ? $_path : new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'  => $_path
        ));
        
        return $path;
    }
    
    /**
     * remove app id from a path
     * 
     * @param string $_flatpath
     * @param Tinebase_Model_Application $_application
     * @return string
     */
    public static function removeAppIdFromPath($_flatpath, $_application)
    {
        $appId = $_application->getId();
        return preg_replace('@^/' . $appId . '@', '', $_flatpath);
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * if flatpath is set, parse it and set the fields accordingly
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        if (array_key_exists('flatpath', $_data)) {
            $this->_parsePath($_data['flatpath']);
        }
    }
    
    /**
     * parse given path: check validity, set container type, do replacements
     * 
     * @param string $_path
     */
    protected function _parsePath($_path)
    {
        $pathParts = $this->_getPathParts($_path);
        
        $this->containerType   = $this->_getContainerType($pathParts);
        $this->containerOwner  = $this->_getContainerOwner($pathParts);
        $this->application     = $this->_getApplication($pathParts);
        $this->container       = $this->_getContainer($pathParts);
        $this->statpath        = $this->_getStatPath($pathParts);
    }
    
    /**
     * get path parts
     * 
     * @param string $_path
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getPathParts($_path)
    {
        $pathParts = explode('/', trim($_path, '/'), 4);       
        if (count($pathParts) < 2) {
            throw new Tinebase_Exception_InvalidArgument('Invalid path: ' . $_path);
        }
        
        return $pathParts;
    }
    
    /**
     * get container type from path
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getContainerType($_pathParts)
    {
        $containerType = $_pathParts[1];
        
        if (! in_array($containerType, array(
            Tinebase_Model_Container::TYPE_PERSONAL,
            Tinebase_Model_Container::TYPE_SHARED,
            Tinebase_Model_Container::TYPE_OTHERUSERS,
        ))) {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $this->containerType);
        }
        
        return $containerType;
    }
    
    /**
     * get container owner from path
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _getContainerOwner($_pathParts)
    {
        $containerOwner = ($this->containerType !== Tinebase_Model_Container::TYPE_SHARED && isset($_pathParts[2])) ? $_pathParts[2] : NULL;
        
        return $containerOwner;
    }
    
    
    /**
     * get application from path
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getApplication($_pathParts)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_pathParts[0]);
        
        return $application;
    }
    
    /**
     * get container from path
     * 
     * @param array $_pathParts
     * @return Tinebase_Model_Container
     */
    protected function _getContainer($_pathParts)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' PATH PARTS: ' . print_r($_pathParts, true));
        
        $container = NULL;
        
        switch ($this->containerType) {
            case Tinebase_Model_Container::TYPE_SHARED:
                if (!empty($_pathParts[2])) {
                    $container = $this->_searchContainerByName($_pathParts[1], Tinebase_Model_Container::TYPE_SHARED);
                }
                break;
                
            case Tinebase_Model_Container::TYPE_PERSONAL:
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                if (!empty($_pathParts[2])) {
                    if ($this->containerType === Tinebase_Model_Container::TYPE_PERSONAL 
                        && $_pathParts[2] !== Tinebase_Core::getUser()->accountLoginName) 
                    {
                        throw new Tinebase_Exception_NotFound('Invalid user name: ' . $_pathParts[2] . '.');
                    }
                    
                    if (!empty($_pathParts[3])) {
                        $subPathParts = explode('/', $_pathParts[3], 2);
                        $container = $this->_searchContainerByName($subPathParts[0], Tinebase_Model_Container::TYPE_PERSONAL);
                    }
                }
                break;
                
            default:
                throw new Tinebase_Exception_NotFound('Invalid path: ' . $_path);
                break;
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
     */
    protected function _searchContainerByName($_name, $_type)
    {
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => $this->application->getId(),
            'name'           => $_name,
            'type'           => $_type,
        )));
        
        if (count($search) !== 1) {
            throw new Tinebase_Exception_NotFound('Container not found: ' . $_name);
        }
        
        return $search->getFirstRecord();
    }    
        
    /**
     * do path replacements (container name => container id, otherUsers => personal, ...)
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _getStatPath($_pathParts)
    {
        $pathParts = $_pathParts;
        
        if ($this->containerType === Tinebase_Model_Container::TYPE_OTHERUSERS) {
            $pathParts[1] = Tinebase_Model_Container::TYPE_PERSONAL;
        }
        
        if (count($pathParts) > 2) {
            $containerPartIdx = ($this->containerType === Tinebase_Model_Container::TYPE_SHARED) ? 2 : 3;
            if (isset($pathParts[$containerPartIdx]) && $this->container && $pathParts[$containerPartIdx] === $this->container->name) {
                $pathParts[$containerPartIdx] = $this->container->getId();
            }
        }
        
        $result = implode('/', $pathParts);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Path to stat: ' . $result);
        
        return $result;
    }
}
