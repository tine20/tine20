<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * interface Sales_Model_Billable_Interface
 *
 * @package     Sales
 * @subpackage  Model
 */
interface Sales_Model_Billable_Interface
{
    /**
     * returns the interval of this billable
     *
     * @return array
     */
    public function getInterval();
    
    /**
     * returns the quantity of this billable
     * 
     * @return float
     */
    public function getQuantity();
}
