<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * timeaccount filter Class
 * @package     Timetracker
 */
class Timetracker_Model_TimeaccountFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Timetracker_Model_TimeaccountIdFilter'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('number', 'title'))),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag'),
        'showClosed'     => array('custom' => true),
        'isBookable'     => array('custom' => true),
    );

    /**
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendFilterSql($_select)
    {
        $db = Tinebase_Core::getDb();
        
        $showClosed = false;
        foreach ($this->_customData as $customData) {
            if ($customData['field'] == 'showClosed' && $customData['value'] == true) {
                $showClosed = true;
            }
        }
        
        if($showClosed){
            // nothing to filter
        } else {
            $_select->where($db->quoteIdentifier('is_open') . ' = 1');
        }
        
        // add container filter
        // NOTE: $this->container is set by the Timeaccount controller
        if (!empty($this->container) && is_array($this->container)) {
            $_select->where($db->quoteInto($db->quoteIdentifier('container_id') . ' IN (?)', $this->container));
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
        
        parent::appendFilterSql($_select);
    }
}
