<?php
/**
 * Tine 2.0
*
* @package     Sales
* @subpackage  Event
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
* @author      Alexander Stintzing <a.stintzing@metaways.de>
*/

/**
 * event fired before a contract gets billed
*
* @package     Tinebase
* @subpackage  Container
*/
class Sales_Event_BeforeBillContract extends Tinebase_Event_Abstract
{
    /**
     * the contract which gets billed
     * 
     * @var Sales_Model_Contract
     */
    public $contract;
    
    /**
     * the reference date
     * 
     * @var Tinebase_DateTime
     */
    public $date;
}
