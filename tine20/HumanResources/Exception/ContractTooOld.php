<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Contract to old to update Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_ContractTooOld extends HumanResources_Exception
{
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = "You are not allowed to change the record if it's older than 2 hours and the start_date is in the past!", $_code = 912) {
        parent::__construct($_message, $_code);
    }
}
