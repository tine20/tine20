<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     
 * @copyright   
 * @author      
 */

/**
 * event class for deleted accounts
 *
 *    MOD: added
 *
 * @package     Admin
 */
class Admin_Event_DeleteAccount extends Tinebase_Event_Abstract
{
    /**
     * array of account ids
     *
     * @var array
     */
    public $accountIds;

}
