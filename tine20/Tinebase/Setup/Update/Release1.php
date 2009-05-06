<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Tinebase_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 1.0
     * - does nothing
     *
     */    
    public function update_0()
    {
    }

    /**
     * update to 1.1
     * - add default app
     */
    public function update_1()
    {
        // add default app preference
        $defaultAppPref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Preference::DEFAULT_APP,
            'value'             => 'Addressbook',
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <special>' . Tinebase_Preference::DEFAULT_APP . '</special>
                </options>'
        ));
        Tinebase_Core::getPreference()->create($defaultAppPref);
        
        $this->setApplicationVersion('Tinebase', '1.1');
    }
}
