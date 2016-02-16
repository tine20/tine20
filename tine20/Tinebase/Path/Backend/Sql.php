<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Path
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */


/**
 * class Tinebase_Path_Backend_Sql
 *
 *
 * @package     Tinebase
 * @subpackage  Path
 */
class Tinebase_Path_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'path';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Path';

    /**
     * @param string $shadowPath
     * @param string $replace
     * @param string $substitution
     * @return int          The number of affected rows.
     */
    public function replacePathForShadowPathTree($shadowPath, $replace, $substitution)
    {
        return $this->_db->update($this->_tablePrefix . $this->_tableName, array(
            'path' => new Zend_Db_Expr($this->_db->quoteInto($this->_db->quoteInto('REPLACE(path, ?', $replace) . ', ?)', $substitution)),
            ),
            $this->_db->quoteInto($this->_db->quoteIdentifier('shadow_path') . ' like "?/%"', $shadowPath)
        );
    }

    /**
     * @param $shadowPath
     * @return int          The number of affected rows.
     */
    public function deleteForShadowPathTree($shadowPath)
    {
        return $this->_db->delete($this->_tablePrefix . $this->_tableName,
            $this->_db->quoteInto($this->_db->quoteIdentifier('shadow_path') . ' like "?/%"', $shadowPath)
        );
    }

    /**
     * @param $shadowPath
     * @param $newPath
     * @param $oldPath
     * @param $newShadowPath
     * @param $oldShadowPath
     */
    public function copyTreeByShadowPath($shadowPath, $newPath, $oldPath, $newShadowPath, $oldShadowPath)
    {
        $select = $this->_db->select()->from($this->_tablePrefix . $this->_tableName, array(
                'path'          => new Zend_Db_Expr($this->_db->quoteInto($this->_db->quoteInto('REPLACE(path, ?', $oldPath) . ', ?)', $newPath)),
                'shadow_path'   => new Zend_Db_Expr($this->_db->quoteInto($this->_db->quoteInto('REPLACE(shadow_path, ?', $oldShadowPath) . ', ?)', $newShadowPath)),
                'record_id'     => 'record_id',
                'creation_time' => new Zend_Db_Expr('NOW()'),
            ))->where($this->_db->quoteInto($this->_db->quoteIdentifier('shadow_path') . ' like "?/%"', $shadowPath));
        $stmt = $this->_db->query($select);
        $entries = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        foreach($entries as $entry) {
            $entry['id'] = Tinebase_Record_Abstract::generateUID();
            $this->_db->insert($this->_tablePrefix . $this->_tableName, $entry);
        }
    }
}