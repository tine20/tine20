<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @version     $Id: Interface.php 9892 2009-08-12 14:31:31Z g.ciyiltepe@metaways.de $
 */

/**
 * Controller interface get Controller instance
 * 
 * 
 * @package     Tinebase
 * @subpackage  Controller
 */
interface Tinebase_Controller_Interface 
{
    /**
     * Instance of Controller Object.
     */
    public static function getInstance();   
}