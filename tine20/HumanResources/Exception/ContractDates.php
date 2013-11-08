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
 * Contract Dates Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_ContractDates extends HumanResources_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Negative Timespace'; // _('Negative Timespace')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The start date of the contract must be before the end date!'; // _('The start date of the contract must be before the end date!')
    
    /**
     * @see SPL Exception
    */
    protected $code = 916;
}
