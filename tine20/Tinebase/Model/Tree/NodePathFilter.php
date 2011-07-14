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
 * Tinebase_Model_Tree_NodePathFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 */
class Tinebase_Model_Tree_NodePathFilter extends Tinebase_Model_Filter_Text 
{
    /**
     * @var array list of allowed operators
     * 
     * @todo add more operators?
     */
    protected $_operators = array(
        0 => 'equals',       
        //1 => 'in',         
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
        $path = $this->_parsePath();
        $node = Tinebase_FileSystem::getInstance()->stat($path);
        
        $field = 'parent_id';
        $action = $this->_opSqlMap[$this->_operator];
        $value = $node->getId();
        
        $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        $_select->where($where);
        
        if (! $this->_container) {
            // @todo add top level filter rules
        }
    }
    
    /**
     * parse given path (filter value): check validity, set container type, do replacements
     * 
     * @return string
     */
    protected function _parsePath()
    {
        $pathParts = $this->_getPathParts();
        $this->_setContainerType($pathParts);
        $path = $this->_doPathReplacements($pathParts);
        
        return $path;
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
     * set container type
     * 
     * @param array $_pathParts
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setContainerType($_pathParts)
    {
        $this->_containerType = $_pathParts[1];
        
        if (! in_array($this->_containerType, array(
            Tinebase_Model_Container::TYPE_PERSONAL,
            Tinebase_Model_Container::TYPE_SHARED,
            Tinebase_Model_Container::TYPE_OTHERUSERS,
        ))) {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $this->_containerType);
        }
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
}
