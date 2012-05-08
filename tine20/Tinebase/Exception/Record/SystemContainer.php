<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * record exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Record_SystemContainer extends Tinebase_Exception_SystemGeneric
{
    /**
     * @var string _('System Container')
     */
    protected $_title = 'System Container';
    
   /**
    * the constructor
    * _('This is a system container which could not be deleted!')
    * 
    * @param string $_message
    * @param int    $_code 
    */
    public function __construct($_message = 'This is a system container which could not be deleted!', $_code=600)
    {
        parent::__construct($_message, $_code);
    }
}
