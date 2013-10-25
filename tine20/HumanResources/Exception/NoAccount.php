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
 * No Account Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_NoAccount extends HumanResources_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'No personal account found'; // _('No personal account found')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'An existing personal account could not be found. Please create one!'; //_('An existing personal account could not be found. Please create one!')
    
    /**
     * @see SPL Exception
    */
    protected $code = 914;
}
