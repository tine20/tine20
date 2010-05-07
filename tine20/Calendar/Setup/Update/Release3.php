<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

class Calendar_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * migragte from class_id (not used) to class
     */
    public function update_0()
    {
        $this->_backend->dropCol('cal_events', 'class_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>class</name>
                <type>text</type>
                <length>40</length>
                <default>PUBLIC</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('cal_events', $declaration, 11);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>class</name>
                <field>
                    <name>class</name>
                </field>
            </index>');
        $this->_backend->addIndex('cal_events', $declaration);
        
        $this->setTableVersion('cal_events', 3);
        $this->setApplicationVersion('Calendar', '3.1');
    }
    
    /**
     * freebusy right/pref -> freebusy grant
     */
    public function update_1()
    {
        /**
         * old const from pref class
         * give all useraccounts grants to view free/busy of the account this preference is yes
         */
        $FREEBUSY = 'freeBusy';
        
        try {
            // get all users with freebusy pref
            $freebusyUserIds = Tinebase_Core::getPreference('Calendar')->getUsersWithPref($FREEBUSY, 1);
            
            // get all affected calendars
            $containerIds = array();
            foreach ($freebusyUserIds as $userId) {
                $containerIds = array_merge($containerIds, Tinebase_Container::getInstance()->getPersonalContainer($userId, 'Calendar', $userId, NULL, TRUE)->getId());
            }
            
            // grant freebusy to anyone for this calendars
            foreach($containerIds as $containerId) {
                $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId, TRUE);
                $anyoneGrant = $containerGrants->filter('account_type', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)->getFirstRecord();
                if (! $anyoneGrant) {
                    $anyoneGrant = new Tinebase_Model_Grants(array(
                        'account_id'   => 0,
                        'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    ));
                    $containerGrants->addRecord($anyoneGrant);
                }
                
                $anyoneGrant->{Tinebase_Model_Grants::GRANT_FREEBUSY} = TRUE;
                
                Tinebase_Container::getInstance()->setGrants($containerId, $containerGrants, TRUE, TRUE);
            }
            
            // drop freeBusy prefs from pref table
            $this->_db->delete(SQL_TABLE_PREFIX . 'preferences', "name LIKE 'freeBusy'");
        
        } catch (Tinebase_Exception_NotFound $nfe) {
            // pref was not found in system -> no user ever set freebusy -> nothing to do
        }
        
        $this->setApplicationVersion('Calendar', '3.2');
    }
    
    /**
     * set container owner as attendee for events from active sync
     */
    public function update_2()
    {
        try {
            Tinebase_Application::getInstance()-> getApplicationByName('ActiveSync');
        
            $tablePrefix = SQL_TABLE_PREFIX;
            
            // get all envets which came vom active sync without creator as attender
            $stmt = $this->_db->query("
            SELECT `cal_events`.`id`, `cal_events`.`created_by`, `contact`.`id` AS `contact_id`, `cal_events`.`container_id`, 
                MAX(
                    `attendee`.`user_type` = 'user' 
                    AND `attendee`.`user_id` != `cal_events`.`created_by`
                ) AS `creatorIsAttender` 
            FROM `{$tablePrefix}cal_events` AS `cal_events`
            INNER JOIN  `{$tablePrefix}acsync_content` AS `acsync_content` ON 
                `acsync_content`.`class` = 'Calendar' 
                AND `acsync_content`.`contentid` = `cal_events`.`id`
                AND TIMESTAMP(`acsync_content`.`creation_time`) - TIMESTAMP(`cal_events`.`creation_time`) <= 0
            LEFT JOIN `{$tablePrefix}cal_attendee` AS `attendee` ON `attendee`.`cal_event_id` = `cal_events`.`id`
            LEFT JOIN `{$tablePrefix}addressbook` AS `contact` ON `contact`.`account_id` = `cal_events`.`created_by`
            GROUP BY `cal_events`.`id`
            HAVING `creatorIsAttender` = 0 OR `creatorIsAttender` IS NULL
            ");
            
            $eventDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            $attendeeBE = new Calendar_Backend_Sql_Attendee();
            
            foreach ($eventDatas as $eventData) {
                try {
                    $attender = new Calendar_Model_Attender(array(
                        'cal_event_id'         => $eventData['id'],
                        'user_id'              => $eventData['contact_id'],
                        'user_type'            => 'user',
                        'role'                 => 'REQ',
                        'quantity'             => 1,
                        'status'               => 'ACCEPTED',
                        'status_authkey'       => Tinebase_Record_Abstract::generateUID(),
                        'displaycontainer_id'  => $eventData['container_id'],
                    ));
                    
                    // add attender
                    $attendeeBE->create($attender);
                    // set modification time
                    $this->_db->update($tablePrefix . 'cal_events', array(
                        'last_modified_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                        'last_modified_by' => $eventData['created_by'],
                        'seq' => new Zend_Db_Expr("seq+1"),
                    ), "`id` = '{$eventData['id']}'");
                } catch (Exception $e) {
                    // ignore...
                }
            }
        } catch (Exception $nfe) {
            // active sync is not installed
        }
        
        $this->setApplicationVersion('Calendar', '3.3');
    }
    
    /**
     * create default persistent filters and set default pref
     */
    public function update_3()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => "All my events", // _("All my events")
            'description'       => "All events I attend", // _("All events I attend")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
            'filters'           => array(
                array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
                )),
            )
        )));
        
        $this->setApplicationVersion('Calendar', '3.4');
    }
}