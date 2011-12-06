<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * record validation exception
 * 
 * @package     Voipmanager
 * @subpackage  Exception
 */
class Voipmanager_Exception_Validation extends Voipmanager_Exception 
{
    /**
    * the constructor
    *
    * @param string $_message
    * @param int $_code (default: 505 Validation)
    */
    public function __construct($_message, $_code = 505)
    {
        parent::__construct($_message, $_code);
    }
}
