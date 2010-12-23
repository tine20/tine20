<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
                array('field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    'DECLINED'
                ))
            )
        )));
    }
}
