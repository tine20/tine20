<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for an EGW2.0 application
 */
abstract class Egwbase_Application_Abstract implements Egwbase_Application_Interface
{
    protected $_appname;
    
    public function getApplicationName()
    {
        return $this->_appname;
    }
}
