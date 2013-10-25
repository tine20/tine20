<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * No  Contract Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_NoContract extends HumanResources_Exception
{
/**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'No contract could be found.'; // _('No contract could be found.')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'Please create a contract for this employee!'; // _('Please create a contract for this employee!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 911;
}
