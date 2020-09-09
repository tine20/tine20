<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * the parsed path record
     * 
     * @var Tinebase_Model_Tree_Node_Path
     */
    protected $_path = NULL;
    
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
     * sets value
     *
     * @param string $_value
     */
    public function setValue($_value)
    {
        // cope with resolved records
        if (is_array($_value)) {
            if (isset($_value['path'])) {
                $_value = $_value['path'];
            } else {
                throw new Tinebase_Exception_UnexpectedValue('pathFilters value can\'t be an array');
            }
        }

        $this->_value = $_value;
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if (! $this->_path && '/' !== $this->_value) {
            $this->_path = Tinebase_Model_Tree_Node_Path::createFromPath($this->_value);
        }
        
        if ('/' === $this->_value || $this->_path->containerType === Tinebase_Model_Tree_Node_Path::TYPE_ROOT) {
            $node = new Tinebase_Model_Tree_Node(array(
                'name' => 'root',
                'path' => '/',
            ), true);
        } else {
            try{
                $node = Tinebase_FileSystem::getInstance()->stat($this->_path->statpath);
                $node->path = $this->_path->flatpath;
            } catch (Exception $e) {
                $node = new Tinebase_Model_Tree_Node(array(
                    'name' => 'root',
                    'path' => '/',
                ), true);
            }

        }

        try {
            Filemanager_Controller_Node::getInstance()->resolveGrants(new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [$node]));
            $convert = new Tinebase_Convert_Tree_Node_Json(Tinebase_Model_Tree_Node::class);
            $result['value'] = $convert->fromTine20Model($node);
        } catch (Exception $e) {
            $result['value'] = $node->toArray();
        }
        
        return $result;
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
    }
    
    /**
     * parse given path (filter value): check validity, set container type, do replacements
     */
    protected function _parsePath()
    {
        if ('/' === $this->_value) {
            if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::VIEW_QUOTA_USAGE)) {
                throw new Tinebase_Exception_AccessDenied('You don\'t have the right to run this application');
            }
            return;
        }

        $this->_path = Tinebase_Model_Tree_Node_Path::createFromPath($this->_value);
        
        if (! $this->_options['ignoreAcl'] && ! Tinebase_Core::getUser()->hasRight($this->_path->application->name, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('You don\'t have the right to run this application');
        }
    }

    /**
     * adds parent id filter sql
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    protected function _addParentIdFilter($_select, $_backend)
    {
        if ('/' === $this->_value) {
            $parentIdFilter = new Tinebase_Model_Filter_Text('parent_id', 'isnull', '');
        } else {
            $node = Tinebase_FileSystem::getInstance()->stat($this->_path->statpath);
            $parentIdFilter = new Tinebase_Model_Filter_Text('parent_id', 'equals', $node->getId());
        }
        $parentIdFilter->appendFilterSql($_select, $_backend);
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $_parent
     */
    public function setParent(Tinebase_Model_Filter_FilterGroup $_parent)
    {
        parent::setParent($_parent);

        if ($_parent->getRootParent()->isInSetFromUser()) {
            $this->_value = Filemanager_Controller_Node::getInstance()->addBasePath($this->_value);
        }
    }
}
