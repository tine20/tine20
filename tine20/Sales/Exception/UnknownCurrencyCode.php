<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * UnknownCurrencyCode exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_UnknownCurrencyCode extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Unknown Currency Code'; // _('Unknown Currency Code')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The Currency Code you tried to use is not valid. Please use a valid Currency Code as defined in ISO 4217.'; // _('The Currency Code you tried to use is not valid. Please use a valid Currency Code as defined in ISO 4217.')
    
    /**
     * @see SPL Exception
     */
    protected $code = 910;
}
