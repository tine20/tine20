<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Login Failed Exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_MonthFormat extends Tinebase_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Wrong month format!'; // _('Wrong month format!')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The month must have the format YYYY-MM!'; //_('The month must have the format YYYY-MM!')
    
    /**
     * @see SPL Exception
    */
    protected $code = 913;
}
