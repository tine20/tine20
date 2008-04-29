<?php

/**
 * interface for backend class
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c); 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de);
 * @version     $Id: Interface.php 1735 2008-04-05 20:08:37Z lkneschke $
 *
 */

/**
 * interface for backend class
 * 
 * @package     Setup
 */
abstract class Setup_Backend_Abstract implements Setup_Backend_Interface
{
    
    /*
    * execute insert statement for default values (records);
    * handles some special fields, which can't contain static values
    * 
    * @param SimpleXMLElement $_record
    */
    abstract public function execInsertStatement(SimpleXMLElement $_record);

    
}
