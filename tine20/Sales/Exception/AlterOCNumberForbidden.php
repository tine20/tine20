<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * AlterOCNumberForbidden exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_AlterOCNumberForbidden extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'No Right to alter the Number'; // _('No Right to alter the Number')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'You are not allowed to alter the number afterwards!'; // _('You are not allowed to alter the number afterwards!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 915;
}
