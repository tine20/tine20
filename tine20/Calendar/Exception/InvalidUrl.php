<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Invalid URL Exception
 *
 * @package Calendar
 */
class Calendar_Exception_InvalidUrl extends Calendar_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Invalid URL given'; // _('Invalid URL given')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The URL you used is invalid. Please use a valid one!'; //_('The URL you used is invalid. Please use a valid one!')
    
    /**
     * @see SPL Exception
    */
    protected $code = 911;
}
