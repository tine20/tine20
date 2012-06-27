<?php
/**
 * Tine 2.0
 *
 * @package     CustomEventHooks
 * @subpackage  Redis
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * custom event handlers for redis
 * NOTE: can be used as template for custom jobs pushed to redis queue
 *
 * @package     CustomEventHooks
 * @subpackage  Redis
 */
class CustomEventHooks
{
    /**
     * implement logic for each controller in this function
     * 
     * @param Tinebase_Event_Abstract $_eventObject
     */
    public static function handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $data = array(
                    'user' => $_eventObject->account->toArray(),
                    'action'  => 'create',
                );
                break;
/*                
            case 'Admin_Event_DeleteAccount':
                break;
            case 'Admin_Event_UpdateGroup':
                break;
            case 'Admin_Event_AddGroupMember':
                break;
            case 'Admin_Event_RemoveGroupMember':
                break;
            case 'Tinebase_Event_Container_BeforeCreate':
                break;
*/
            default:
                // do nothing
                return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') Push to Redis queue: ' . print_r($data, TRUE));
        
        $redisQueue = new Tinebase_Redis_Queue();
        $redisQueue->push($data);
    }
}
