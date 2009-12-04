<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 *
 */

/**
 * UnexpectedValue exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_UnexpectedValue extends UnexpectedValueException // extends Tinebase_Exception
{
    public function __construct($_message, $_code=505) {
        parent::__construct($_message, $_code);
    }    
}
