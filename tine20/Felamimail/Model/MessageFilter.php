<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:FolderFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * cache entry filter Class
 * @package     Felamimail
 */
class Felamimail_Model_MessageFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'folder_id'     => array('filter' => 'Tinebase_Model_Filter_Id'), 
        'subject'       => array('filter' => 'Tinebase_Model_Filter_Text'), 
        'from'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'received'      => array('filter' => 'Tinebase_Model_Filter_Date'),
    // custom filters
        'to'            => array('custom' => true),
        'cc'            => array('custom' => true),
        'bcc'           => array('custom' => true),
    );

    /**
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                       $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @return void
     * 
     * @todo use group of Tinebase_Model_Filter_Text with OR?
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        $foreignTables = $_backend->getForeignTableNames();
        
        foreach ($this->_customData as $customData) {
            // add conditions
            $tablename  = $_backend->getTablePrefix() . $foreignTables[$customData['field']];
            $fieldName  = $tablename . '.name';
            $fieldEmail = $tablename . '.email';
            $value      = '%' . $customData['value'] . '%';
            
            $_select->joinLeft(
                $tablename, 
                $tablename . '.message_id = ' . $_backend->getTableName() . '.id'
            )->where(
                $db->quoteInto($fieldName  . ' LIKE ?', $value) . ' OR ' .
                $db->quoteInto($fieldEmail . ' LIKE ?', $value)
            );
            
            // create text filter
            //$textFilter = new Tinebase_Model_Filter_Text($tablename . '.' . $customData['field'], $customData['operator'], $customData['value']);
            //$textFilter->appendFilterSql($_select, $_backend);
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
    }
}
