<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Text
 *
 * filters one filterstring in one property
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ExternalFullText extends Tinebase_Model_Filter_FullText
{

    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (!isset($_options['idProperty'])) {
            throw new Tinebase_Exception_InvalidArgument('a idProperty must be specified in the options');
        }
        parent::_setOptions($_options);
    }

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (null === ($fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)) || ! $fulltextConfig instanceof Tinebase_Config_Struct) {
            throw new Tinebase_Exception_UnexpectedValue('no fulltext configuration found');
        }

        if ('Sql' !== $fulltextConfig->{Tinebase_Config::FULLTEXT_BACKEND}) {
            throw new Tinebase_Exception_NotImplemented('only Sql backend is implemented currently');
        }

        $db = $_select->getAdapter();
        $select = $db->select()->from(array('external_fulltext' => SQL_TABLE_PREFIX . 'external_fulltext'), array('id'));
        $this->_field = 'text_data';
        if (isset($this->_options['tablename'])) {
            $oldTableName = $this->_options['tablename'];
        } else {
            $oldTableName = null;
        }
        $this->_options['tablename'] = 'external_fulltext';

        parent::appendFilterSql($select, $_backend);

        if (null === $oldTableName) {
            unset($this->_options['tablename']);
        } else {
            $this->_options['tablename'] = $oldTableName;
        }
        $this->_field = $this->_options['idProperty'];
        $stmt = $select->query(Zend_Db::FETCH_NUM);
        $ids = array();
        foreach($stmt->fetchAll() as $row) {
            $ids[] = $row[0];
        }

        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($ids) ? new Zend_Db_Expr('NULL') : $ids);
    }
}