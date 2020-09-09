<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * native tine 2.0 events sql backend attendee class
 *
 * @package Calendar
 */
class Calendar_Backend_Sql_Attendee extends Tinebase_Backend_Sql_Abstract
{
    /**
     * event foreign key column
     */
    const FOREIGNKEY_EVENT = 'cal_event_id';

    const TABLENAME = 'cal_attendee';
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = self::TABLENAME;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Calendar_Model_Attender';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     * @param Tinebase_Model_Container $sourceContainer
     * @param Tinebase_Model_Container $destinationContainer
     */
    public function moveEventsToContainer(Tinebase_Model_Container $sourceContainer, Tinebase_Model_Container $destinationContainer)
    {
        $this->_db->update($this->_tablePrefix . $this->_tableName, array('displaycontainer_id' => $destinationContainer->getId()),
            $this->_db->quoteInto($this->_db->quoteIdentifier('displaycontainer_id') . ' = ?', $sourceContainer->getId()));
    }

    /**
     * @param string $oldContactId
     * @param string $newContactId
     */
    public function replaceContactId($oldContactId, $newContactId)
    {
        $this->_db->update($this->_tablePrefix . $this->_tableName, array('user_id' => $newContactId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('user_id') . ' = ?', $oldContactId));
    }
}
