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
        $path = $this->_value;
        if ($this->_container) {
            $path = $this->_replaceContainerNameWithId($path);
        }
        
        $node = Tinebase_FileSystem::getInstance()->stat($path);
        
        $field = 'parent_id';
        $action = $this->_opSqlMap[$this->_operator];
        $value = $node->getId();
        
        $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        $_select->where($where);
    }
    
    /**
     * replace container name in path with id
     * 
     * @param string $_path
     * @return string
     * @throws Tinebase_Exception
     */
    protected function _replaceContainerNameWithId($_path)
    {
        if (substr_count($_path,  $this->_container->name) === 1) {
            // only one occurrence
            $result = str_replace($this->_container->name, $this->_container->getId(), $_path);
        } else if ($this->_container->name === Tinebase_Model_Container::TYPE_SHARED) {
            // only replace the occurence after shared
            $result = preg_replace(
                '@' . Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_container->name . '@', 
                Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_container->getId(), 
                $_path
            );
        } else {
            // FIXME add regexp that only replaces the container name
            throw new Tinebase_Exception('Container name and user name are the same!');
        }
        
        return $result;
    }
}
