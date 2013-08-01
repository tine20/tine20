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
 * No  Contract Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_RemainingNotBookable extends HumanResources_Exception
{
    protected $_nearest_record = NULL;

    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = "It's only allowed to book remaining vacation days from years in the past!", $_code = 913) {
        parent::__construct($_message, $_code);
    }
}
