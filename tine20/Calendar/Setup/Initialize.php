<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TineInitial.php 9535 2009-07-20 10:30:05Z p.schuele@metaways.de $
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
     * initialize application
     *
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        parent::_createInitialRights($_application, $_options);
        $this->_initializeFavorites();
    }
    
    protected function _initializeFavorites()
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
        
        // set this pfilter as defautl pref
        $preference = new Tinebase_Model_Preference(array(
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'name'              => Calendar_Preference::DEFAULTPERSISTENTFILTER,
                'value'             => $myEventsPFilter->getId(),
                'account_id'        => '0',
                'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
                'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Calendar_Preference::DEFAULTPERSISTENTFILTER . '</special>
                    </options>'
            ));
        Tinebase_Core::getPreference('Calendar')->create($preference);
    }
}