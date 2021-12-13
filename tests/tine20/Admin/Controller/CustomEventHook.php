<?php
/**
 * tine - https://www.tine-groupware.de/
 *
 * custom event hook for \Admin_Controller_UserTest::testCustomEventHookUserAdd
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Admin_Controller_CustomEventHook
{
    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    public function handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                echo 'Handled event Admin_Event_AddAccount';
                break;
        }
    }
}
