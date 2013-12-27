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
 * InvoiceAlreadyClearedDelete exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_InvoiceAlreadyClearedDelete extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Invoice is cleared already'; // _('Invoice is cleared already')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The Invoice you tried to delete is cleared already, so deleting is not possible anymore!'; // _('The Invoice you tried to delete is cleared already, so deleting is not possible anymore!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 914;
}
