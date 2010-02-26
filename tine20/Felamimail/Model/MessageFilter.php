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
 * @todo        replace some 'custom' filters with normal filter classes
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
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Felamimail_Model_Message';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('subject', 'from'))
        ),
        'folder_id'     => array('filter' => 'Tinebase_Model_Filter_Id'), 
        'subject'       => array('filter' => 'Tinebase_Model_Filter_Text'), 
        'from'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'received'      => array('filter' => 'Tinebase_Model_Filter_DateTime'),
    // custom filters
        'to'            => array('custom' => true),
        'cc'            => array('custom' => true),
        'bcc'           => array('custom' => true),
        'flags'         => array('custom' => true),
        'account_id'    => array('custom' => true),
        'messageuid'    => array('filter' => 'Tinebase_Model_Filter_Int'),
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
            
            if ($customData['field'] == 'account_id') {
                // get all folders of account
                $folderFilter = new Felamimail_Model_FolderFilter(array(
                    array('field' => 'account_id',  'operator' => 'equals', 'value' => $customData['value'])
                ));
                $folderBackend = new Felamimail_Backend_Folder();
                $folderIds = $folderBackend->search($folderFilter, NULL, TRUE);
                $_select->where($db->quoteInto(
                    $db->quoteIdentifier($_backend->getTableName() . '.folder_id') . ' IN (?)', 
                    $folderIds
                ));
                
            } else {
                
                // add conditions
                $tablename  = $_backend->getTablePrefix() . $foreignTables[$customData['field']];
                if ($customData['field'] == 'flags') {
                    $fieldName = 'flag';
                } else {
                    $fieldName  = $tablename . '.name';
                    $fieldEmail = $tablename . '.email';
                }
                
                // add filter value
                $value      = '%' . $customData['value'] . '%';
                                
                if ($customData['field'] == 'flags') {
                    if ($customData['operator'] == 'equals' || $customData['operator'] == 'contains') {
                        $_select->having($db->quoteInto('flags LIKE ?', $value));
                    } else {
                        $_select->having($db->quoteInto('flags NOT LIKE ? OR flags IS NULL', $value));
                    }
                } else {
                    $_select->where(
                        $db->quoteInto($fieldName  . ' LIKE ?', $value) . ' OR ' .
                        $db->quoteInto($fieldEmail . ' LIKE ?', $value)
                    );
                }
            }
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
    }
}
