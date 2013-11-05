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
 * Contract Overlap Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_ContractOverlap extends HumanResources_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Contracts overlap'; // _('Contracts overlap')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The contracts must not overlap!'; // _('The contracts must not overlap!')
    
    /**
     * @see SPL Exception
    */
    protected $code = 915;
}
