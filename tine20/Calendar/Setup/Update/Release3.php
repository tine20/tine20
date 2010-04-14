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
}