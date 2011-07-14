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
        $path = $this->_doPathReplacements($this->_value);
        $node = Tinebase_FileSystem::getInstance()->stat($path);
        
        $field = 'parent_id';
        $action = $this->_opSqlMap[$this->_operator];
        $value = $node->getId();
        
        $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        $_select->where($where);
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
     * @param string $_path
     * @return string
     * 
     * @todo add top level paths (only type given)
     */
    protected function _doPathReplacements($_path)
    {
        $pathParts = explode('/', trim($_path, '/'), 4);
        
        if (count($pathParts) < 2) {
            throw new Tinebase_Exception_InvalidArgument('Invalid path: ' . $_path);
        }

        if (count($pathParts) === 2) {
            // @todo get all containers user has read access to
            throw new Tinebase_Exception_NotImplemented('Not implemented yet.');
        }
            
        if ($pathParts[1] === Tinebase_Model_Container::TYPE_OTHERUSERS) {
            $pathParts[1] = Tinebase_Model_Container::TYPE_PERSONAL;
        }
        
        $containerPartIdx = ($pathParts[1] === Tinebase_Model_Container::TYPE_SHARED) ? 2 : 3;
        
        if (isset($pathParts[$containerPartIdx]) && $this->_container && $pathParts[$containerPartIdx] === $this->_container->name) {
            $pathParts[$containerPartIdx] = $this->_container->getId();
        }
        
        $result = implode('/', $pathParts);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Path to stat: ' . $result);
        
        return $result;
    }
}
