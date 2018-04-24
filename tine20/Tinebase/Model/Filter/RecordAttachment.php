<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Filter_RecordAttachment
 *
 * filters for records with/out attachments or with attachments that match a tree_node filter
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_RecordAttachment extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = [
        //'has',
        'in',
    ];

    /**
     * @var Tinebase_Model_Tree_Node_Filter
     */
    protected $_subFilter = null;

    /**
     * @var string|Tinebase_Record_Abstract
     */
    protected $_model;

    /**
     * @var string
     */
    protected $_idProperty;

    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (!isset($_options['modelName'])) {
            throw new Tinebase_Exception_InvalidArgument(__CLASS__ . ' requires a option modelName set');
        }
        $this->_model = $_options['modelName'];

        if (!isset($_options['idProperty'])) {
            throw new Tinebase_Exception_InvalidArgument(__CLASS__ . ' requires a option idProperty set');
        }
        $this->_idProperty = $_options['idProperty'];

        parent::_setOptions($_options);
    }
    /**
     * set value
     *
     * NOTE: incoming ids will be rewritten to their corresponding paths
     * NOTE: incoming *Node operators will be rewritten to their corresponding pathes
     *
     * @param mixed $_value
     *
    public function setValue($_value)
    {
        parent::setValue($_value);

        if ('has' === $this->_operator) {
            $this->_value = (bool)$_value;

        } elseif ('in' === $this->_operator) {

        }
    }*/

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        list($app,) = explode('_', $this->_model, 2);
        $fs = Tinebase_FileSystem::getInstance();
        $basePath = $fs->getApplicationBasePath($app, Tinebase_FileSystem::FOLDER_TYPE_RECORDS) . '/' .
            $this->_model;
        try {
            $modelNode = $fs->stat($basePath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->_appendNoMatch($_select);
            return;
        }

        $recordNodes = $fs->search(new Tinebase_Model_Tree_Node_Filter([
            ['field' => 'parent_id', 'operator' => 'equals', 'value' => $modelNode->getId()]
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_AND, ['ignoreAcl' => true]), null,
            [Tinebase_Backend_Sql_Abstract::IDCOL, 'name']);

        if (empty($recordNodes)) {
            $this->_appendNoMatch($_select);
            return;
        }

        $subFilter = new Tinebase_Model_Tree_Node_Filter($this->_value,
            Tinebase_Model_Filter_FilterGroup::CONDITION_AND, ['ignoreAcl' => true]);
        $subFilter->addFilter($subFilter->createFilter([
            'field' => 'parent_id', 'operator' => 'in', 'value' => array_keys($recordNodes)
        ]));

        $nodes = $fs->search($subFilter, null, [Tinebase_Backend_Sql_Abstract::IDCOL, 'parent_id']);

        if (empty($nodes)) {
            $this->_appendNoMatch($_select);
            return;
        }

        $ids = [];
        foreach($nodes as $pId) {
            $ids[] = $recordNodes[$pId];
        }

        $_select->where($this->_db->quoteIdentifier([$_backend->getTableName(), $this->_idProperty]) .
            $this->_db->quoteInto(' in (?)', $ids));
    }

    /**
     * @param Zend_Db_Select $_select
     */
    protected function _appendNoMatch($_select)
    {
        $_select->where('1 = 0');
    }
}