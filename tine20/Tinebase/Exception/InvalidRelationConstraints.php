<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Tinebase exception with exception data
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_InvalidRelationConstraints extends Tinebase_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Invalid Relations'; // _('Invalid Relations')
    
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
     public function __construct($_message = "You tried to create a relation which is forbidden by the constraints config of one of the models.", $_code = 912) {
        // _("You tried to create a relation which is forbidden by the constraints config of one of the models.")
            parent::__construct($_message, $_code);
    }
}
