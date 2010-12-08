<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class for Felamimail initialization
 * 
 * @package     Setup
 */
class Felamimail_Setup_Initialize extends Setup_Initialize
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
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $myInboxPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Felamimail_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All INBOXES", // _("All INBOXES")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
            'model'             => 'Felamimail_Model_MessageFilter',
            'filters'           => array(
                array('field' => 'path'    , 'operator' => 'in', 'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        )));
    }
}
