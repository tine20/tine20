<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Calendar initialization
 * 
 * @package     Setup
 */
class Calendar_Setup_Initialize extends Setup_Initialize
{
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
        );
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Calendar_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All events I attend", // _("All events I attend")
            'filters'           => array(
                array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
                )),
                array('field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    'DECLINED'
                ))
            )
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Awaiting response", //_("Awaiting response")
            'description'       => "Events I have not yet responded to", // _("Events I have not yet responded to")
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
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Declined events", //_("Declined events")
            'description'       => "Events I have declined", // _("Events I have declined")
            'filters'           => array(
                array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
                )),
                array('field' => 'attender_status'    , 'operator' => 'in', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED,
                )),
            )
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "I'm organizer", //_("I'm organizer")
            'description'       => "Events I'm the organizer of", // _("Events I'm the organizer of")
            'filters'           => array(
                array('field' => 'organizer', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT)
            )
        ))));
    }
    
    /**
     * init favorites
     */
    protected function _initializeKeyFields()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $attendeeRolesConfig = array(
            'name'    => Calendar_Config::ATTENDEE_ROLES,
            'records' => array(
                array('id' => 'REQ', 'value' => 'Required', 'system' => true), //_('Required')
                array('id' => 'OPT', 'value' => 'Optional', 'system' => true), //_('Optional')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_ROLES,
            'value'             => json_encode($attendeeRolesConfig),
        )));
        
        $attendeeStatusConfig = array(
            'name'    => Calendar_Config::ATTENDEE_STATUS,
            'records' => array(
                array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true), //_('No response')
                array('id' => 'ACCEPTED',     'value' => 'Accepted',    'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Accepted')
                array('id' => 'DECLINED',     'value' => 'Declined',    'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Declined')
                array('id' => 'TENTATIVE',    'value' => 'Tentative',   'icon' => 'images/calendar-response-tentative.png',               'system' => true), //_('Tentative')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_STATUS,
            'value'             => json_encode($attendeeStatusConfig),
        )));
    }
}
