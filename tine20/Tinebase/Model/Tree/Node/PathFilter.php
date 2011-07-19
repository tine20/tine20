<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Tree_Node_PathFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 */
class Tinebase_Model_Tree_Node_PathFilter extends Tinebase_Model_Filter_Text 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       
    );
    
    /**
     * a path could belong to one container
     * 
     * @var Tinebase_Model_Container
     */
    protected $_container = NULL;
    
    /**
     * container type
     * 
     * @var string
     */
    protected $_containerType = NULL;
    
    /**
     * container owner (account login name)
     * 
     * @var string
     */
    protected $_containerOwner = NULL;
    
    /**
     * stat path to fetch node with
     * 
     * @var string
     */
    protected $_statPath = NULL;
    
    /**
     * application
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application = NULL;
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_Grants::GRANT_READ
    );
    
    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        $_options['ignoreAcl'] = isset($_options['ignoreAcl']) ? $_options['ignoreAcl'] : false;
        
        $this->_options = $_options;
    }
        
    /**
     * set container
     * 
     * @param Tinebase_Model_Container $_container
     */
    public function setContainer(Tinebase_Model_Container $_container)
    {
        $this->_container = $_container;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_parsePath();
        
        $this->_addParentIdFilter($_select, $_backend);
        
        if (! $this->_container) {
            $this->_addContainerTypeFilter($_select, $_backend);
        }
    }
    
    /**
     * parse given path (filter value): check validity, set container type, do replacements
     */
    protected function _parsePath()
    {
        $pathParts = $this->_getPathParts();
        
        $this->_containerType   = $this->_getContainerType($pathParts);
        $this->_containerOwner  = $this->_getContainerOwner($pathParts);
        $this->_application     = $this->_getApplication($pathParts);
        $this->_statPath        = $this->_doPathReplacements($pathParts);
    }
    
    /**
     * get path parts
     * 
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getPathParts()
    {
        $pathParts = explode('/', trim($this->_value, '/'), 4);       
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
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $this->_containerType);
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
        $containerOwner = ($this->_containerType !== Tinebase_Model_Container::TYPE_SHARED && isset($_pathParts[2])) ? $_pathParts[2] : NULL;
        
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
        
        if (! $this->_options['ignoreAcl'] && ! Tinebase_Core::getUser()->hasRight($application->name, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('You don\'t have the right to run this application');
        }
        
        return $application;
    }
        
    /**
     * do path replacements (container name => container id, otherUsers => personal, ...)
     * 
     * [0] => app id [required]
     * [1] => type [required]
     * [2] => container | accountLoginName
     * [3] => container | directory
     * [4] => directory
     * [5] => ...
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _doPathReplacements($_pathParts)
    {
        $pathParts = $_pathParts;
        
        if ($this->_containerType === Tinebase_Model_Container::TYPE_OTHERUSERS) {
            $pathParts[1] = Tinebase_Model_Container::TYPE_PERSONAL;
        }
        
        if (count($pathParts) > 2) {
            $containerPartIdx = ($this->_containerType === Tinebase_Model_Container::TYPE_SHARED) ? 2 : 3;
            if (isset($pathParts[$containerPartIdx]) && $this->_container && $pathParts[$containerPartIdx] === $this->_container->name) {
                $pathParts[$containerPartIdx] = $this->_container->getId();
            }
        }
        
        $result = implode('/', $pathParts);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Path to stat: ' . $result);
        
        return $result;
    }
    
    /**
     * adds parent id filter sql
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    protected function _addParentIdFilter($_select, $_backend)
    {
        $node = Tinebase_FileSystem::getInstance()->stat($this->_statPath);

        $parentIdFilter = new Tinebase_Model_Filter_Text('parent_id', 'equals', $node->getId());
        $parentIdFilter->appendFilterSql($_select, $_backend);
    }

    /**
     * adds container type filter sql
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    protected function _addContainerTypeFilter($_select, $_backend)
    {
        $currentAccount = Tinebase_Core::getUser();
        $appName        = $this->_application->name;
        $ignoreAcl      = $this->_options['ignoreAcl'];
        
        switch ($this->_containerType) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $names = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, $appName,
                    $currentAccount, $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                break;
            case Tinebase_Model_Container::TYPE_SHARED:
                $names = Tinebase_Container::getInstance()->getSharedContainer($currentAccount, $appName,
                    $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                break;
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                if ($this->_containerOwner) {
                    $owner = Tinebase_User::getInstance()->getFullUserByLoginName($this->_containerOwner);
                    $names = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, $appName,
                        $owner, $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                } else {
                    $users = Tinebase_Container::getInstance()->getOtherUsers($currentAccount, $appName, $this->_requiredGrants);
                    $names = array();
                    // @todo use getMultiple for full users
                    foreach ($users as $user) {
                        $names[] = Tinebase_User::getInstance()->getFullUserById($user)->accountLoginName;
                    }
                }
                break;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Filter names: ' . print_r($names, TRUE));
        
        $nameFilter = new Tinebase_Model_Filter_Text('name', 'in', $names);
        $nameFilter->appendFilterSql($_select, $_backend);
    }
}
