<?php

/**
 * Abstract class for an EGW2.0 application with Http interface
 * 
 * Note, that the Http inerface in egw 2.0 is used to generate the base layouts
 * in new browser windows.
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
abstract class Egwbase_Application_Http_Abstract extends Egwbase_Application_Abstract
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array of filenames
     */
    public function getJsFilesToInclude()
    {
        return $this->_appendFileTime("{$this->_appname}/js/{$this->_appname}.js");
    }
    
    /**
     * Retruns all CSS files which must be inclued for this app
     *
     * @return array of filenames
     */
    public function getCssFilesToInclude()
    {
        return self::_appendFileTime("{$this->_appname}/css/{$this->_appname}.css");
    }
    
    /**
     * Returns initial data which is send to the app at createon time.
     *
     * This function returns the needed structure, to display the initial tree, 
     * after the the login. Additional tree items get loaded on demand see 
     * getSubTree.
     * 
     * $param string $_location 
     *
     * @return 
     */
    public function getInitialData()
    {
        return;
    }
    
    public static function _appendFileTime( $_file )
    {
        $path = dirname(__FILE__). "/../../../$_file";
        return "$_file?". filectime($path);
    }
}