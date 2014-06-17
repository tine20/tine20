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
 * DeletePreviousInvoice exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_DeletePreviousInvoice extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Following Invoices Found'; // _('Following Invoices Found')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'There is an invoice for the same contract after this one. Please delete the following invoice(s) before deleting this one!'; // _('There is an invoice for the same contract after this one. Please delete the following invoice(s) before deleting this one!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 916;
}
