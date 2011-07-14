<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Timetracker_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * remove mapping of Timetracker_Model_TimeaccountGrants to Tinebase_Model_Grants
     * 
     * @return void
     */
    public function update_0()
    {
        $mapping = array(
            Tinebase_Model_Grants::GRANT_READ     => Timetracker_Model_TimeaccountGrants::BOOK_OWN,
            Tinebase_Model_Grants::GRANT_ADD      => Timetracker_Model_TimeaccountGrants::VIEW_ALL,
            Tinebase_Model_Grants::GRANT_EDIT     => Timetracker_Model_TimeaccountGrants::BOOK_ALL,
            Tinebase_Model_Grants::GRANT_DELETE   => Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
        );
        
        $tablePrefix = SQL_TABLE_PREFIX;
        $timetracker = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');

        // get all timetracker containers
        $select = new Zend_Db_Select($this->_db);
        $select->from($tablePrefix . 'container', 'id')->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $timetracker->getId()));
        $containerIds = $this->_db->fetchAll($select);
        
        if (count($containerIds) > 0) {
            foreach ($mapping as $oldgrant => $newgrant) {
                $bind = array(
                    'account_grant' => $newgrant
                );
                $where = array(
                    $this->_db->quoteInto($this->_db->quoteIdentifier('account_grant') . ' = ?', $oldgrant),
                    $this->_db->quoteInto($this->_db->quoteIdentifier('container_id') . ' IN (?)', $containerIds),
                );
                $this->_db->update($tablePrefix . 'container_acl', $bind, $where);
            }
        }
        
        $this->setApplicationVersion('Timetracker', '4.1');
    }

    /**
     * update timesheet favorites as is_billable / is_cleared filters have changed
     * 
     * @return void
     */
    public function update_1()
    {
        $timetracker = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
        
        $select = new Zend_Db_Select($this->_db);
        $select->from(SQL_TABLE_PREFIX . 'filter', array('id', 'filters'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $timetracker->getId()))
            ->where($this->_db->quoteIdentifier('filters') . " LIKE '%\"is_billable\"%' OR " . $this->_db->quoteIdentifier('filters') . " LIKE '%\"is_cleared\"%'");
        $result = $this->_db->fetchAssoc($select);
        
        foreach ($result as $id => $data) {
            $patterns = array(
                '/"is_billable"/',
                '/"is_cleared"/',
            );
            $replacements = array(
                '"is_billable_combined"',
                '"is_cleared_combined"',
            );
            
            $filters = preg_replace($patterns, $replacements, $data['filters']);
            $bind = array(
                'filters' => $filters
            );
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $id),
            );
            $this->_db->update(SQL_TABLE_PREFIX . 'filter', $bind, $where);
        }
        
        $this->setApplicationVersion('Timetracker', '4.2');
    }
    
    /**
     * update to 5.0
     */
    public function update_2()
    {
        $this->setApplicationVersion('Timetracker', '5.0');
    }
}
