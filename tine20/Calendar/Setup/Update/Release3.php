<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
                        'last_modified_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
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
            'name'              => Calendar_Preference::DEFAULTPERSISTENTFILTER_NAME,
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
    
    /**
     * add attendee status to default persistent filter
     */
    public function update_4()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $defaultFavorite = $pfe->getByProperty(Calendar_Preference::DEFAULTPERSISTENTFILTER_NAME, 'name');
        $defaultFavorite->bypassFilters = TRUE;
        
        $defaultFavorite->filters = array(
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
            )),
            array('field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                'DECLINED'
            ))
        );
        $pfe->update($defaultFavorite);
        
        $this->setApplicationVersion('Calendar', '3.5');
    }
    
    /**
     * add all exceptions to exdate of baseevent
     */
    public function update_5()
    {
        // get all event uid's from event with exceptions
        $tablePrefix = SQL_TABLE_PREFIX;
        
        $this->_db->query("
            INSERT INTO `{$tablePrefix}cal_exdate` SELECT UUID() AS `id`, `calids`.`id` AS `cal_event_id`, SUBSTRING(`cal_events`.`recurid`, -19) AS `exdate`
            FROM `{$tablePrefix}cal_events` AS `cal_events`
            JOIN `{$tablePrefix}cal_events` AS `calids` ON (`cal_events`.`uid` = `calids`.`uid` AND `calids`.`recurid` IS NULL)
            WHERE `cal_events`.`recurid` IS NOT NULL
        ");
        
        $this->setApplicationVersion('Calendar', '3.6');
    }
    
    /**
     * calendar colors: state -> container
     */
    public function update_6()
    {
        $tablePrefix = SQL_TABLE_PREFIX;
        
        // mialena fixed color map
        $colorMap = array(
            9  => '#FF6600',
            16 => '#FF0000',
            21 => '#3366FF',
            24 => '#FF00FF',
            27 => '#00FF00',
            30 => '#993366',
        );
        
        // $containerId => array($colorId => $usageCount)
        $colorHistogram = array();
        
        $stmt = $this->_db->query("
            SELECT *
            FROM `{$tablePrefix}state` AS `state`
        ");
        
        $states = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($states as $state) {
            $data = Zend_Json::decode($state['data']);
            if (array_key_exists('cal-color-mgr-containers', $data)) {
                $colorMgrData = Tinebase_State::decode($data['cal-color-mgr-containers']);
                $containerColorMap = $colorMgrData['colorMap'];
                foreach ($containerColorMap as $containerId => $colorId) {
                    if (! array_key_exists($containerId, $colorHistogram)) {
                        $colorHistogram[$containerId] = array();
                    }
                    
                    if (! array_key_exists($colorId, $colorHistogram[$containerId])) {
                        $colorHistogram[$containerId][$colorId] = 0;
                    }
                    
                    $colorHistogram[$containerId][$colorId] = $colorHistogram[$containerId][$colorId] + 1;
                }
            }
            
            unset ($data['cal-color-mgr-containers']);
            $this->_db->update($tablePrefix . 'state', array(
                'data' => Zend_Json::encode($data),
            ), "`id` = '{$state['id']}'");
        }
        
        foreach ($colorHistogram as $containerId => $histogram) {
            arsort($histogram);
            reset($histogram);
            $colorId = key($histogram);
            $color = $colorMap[$colorId];
            
            $this->_db->update($tablePrefix . 'container', array(
                'color' => $color,
            ), "`id` = '{$containerId}'");
        }
        
        $this->setApplicationVersion('Calendar', '3.7');
    }
    
    /**
     * create more persistent filters
     */
    public function update_7()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => "Awaiting response",
            'description'       => "Events I have not yet responded to",
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
            'filters'           => array(
                array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
                )),
                array('field' => 'attender_status'    , 'operator' => 'in', 'value' => array(
                    Calendar_Model_Attender::STATUS_NEEDSACTION,
                    Calendar_Model_Attender::STATUS_TENTATIVE,
                    
                )),
            )
        )));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => "Declined events",
            'description'       => "Events I have declined",
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
            'filters'           => array(
                array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
                )),
                array('field' => 'attender_status'    , 'operator' => 'in', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED,
                )),
            )
        )));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => "I'm organizer",
            'description'       => "Events I'm the organizer of",
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
            'filters'           => array(
                array('field' => 'organizer', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT)
            )
        )));
        
        $this->setApplicationVersion('Calendar', '3.8');
    }
    
    /**
     * add a calendar for each resource
     */
    public function update_8()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>container_id</name>
                <type>integer</type>
            </field>');
        $this->_backend->addCol('cal_resources', $declaration, 1);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>cal_resources::container_id--container::id</name>
                <field>
                    <name>container_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>container</table>
                    <field>id</field>
                </reference>
            </index>');
        $this->_backend->addForeignKey('cal_resources', $declaration);
        
        $this->setTableVersion('cal_resources', 2);
        $this->setApplicationVersion('Calendar', '3.9');
        
        // give existing resources a container
        $rb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Calendar_Model_Resource', 
            'tableName' => 'cal_resources'
        ));
        $resources = $rb->getAll();
        
        foreach($resources as $resource) {
            $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'              => $resource->name,
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'          => $resource->getId(),
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            )), NULL, TRUE);
            
            // remove default admin
            $grants = Tinebase_Container::getInstance()->setGrants($container->getId(), new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                array(
                    'account_id'      => '0',
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Tinebase_Model_Grants::GRANT_FREEBUSY  => true
                )
            )), TRUE, FALSE);
            
            $resource->container_id = $container->getId();
            $rb->update($resource);
        }
    }
    
    /**
     * update to 3.10
     * @return void
     */
    public function update_9()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>user_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>role</name>
                <type>text</type>
                <length>32</length>
                <default>REQ</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>NEEDS-ACTION</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $this->setTableVersion('cal_attendee', 2);
        
        $this->setApplicationVersion('Calendar', '3.10');
    }
    
    /**
     * update to 3.11
     * @return void
     */
    public function update_10()
    {
        $this->validateTableVersion('cal_attendee', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>transp</name>
                <type>text</type>
                <length>40</length>
                <default>OPAQUE</default>
            </field>
        ');
        $this->_backend->addCol('cal_attendee', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>alarm_ack_time</name>
                <type>datetime</type>
            </field>
        ');
        $this->_backend->addCol('cal_attendee', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>alarm_snooze_time</name>
                <type>datetime</type>
            </field>
        ');
        $this->_backend->addCol('cal_attendee', $declaration);
        
        $this->setTableVersion('cal_attendee', 3);
    
        $this->setApplicationVersion('Calendar', '3.11');
    }
    
    /**
     * update to 4.0
     * @return void
     */
    public function update_11()
    {
        $this->setApplicationVersion('Calendar', '4.0');
    }
}
