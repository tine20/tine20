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
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => 'Calendar_Model_EventFilter',
        );
        
        $myEventsPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "I'm organizer", //_("I'm organizer")
            'description'       => "Events I'm the organizer of", // _("Events I'm the organizer of")
            'filters'           => array(
                array('field' => 'organizer', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT)
            )
        ))));
    }
}
