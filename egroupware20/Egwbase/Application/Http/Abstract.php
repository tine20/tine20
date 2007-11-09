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
abstract class Egwbase_Application_Http_Abstract extends Egwbase_Application_Abstract implements Egwbase_Application_Http_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array of filenames
     */
    public function getJsFilesToInclude()
    {
        $standartFile = "{$this->_appname}/js/{$this->_appname}.js";
        if (file_exists($standartFile)) {
            return array(self::_appendFileTime($standartFile));
        }
        return array();
        
    }
    
    /**
     * Retruns all CSS files which must be inclued for this app
     *
     * @return array of filenames
     */
    public function getCssFilesToInclude()
    {
        $standartFile = "{$this->_appname}/css/{$this->_appname}.css";
        if (file_exists($standartFile)) {
            return array(self::_appendFileTime($standartFile));
        }
        return array();
    }
    
    /**
     * Returns initial data which is send to the app at createon time.
     *
     * When the mainScreen is created, Egwbase_Http_Controler queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial datas are just javascript varialbes declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * varialbe names get prefixed with Egw.<applicationname>
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getInitialMainScreenData()
    {
        return array();
    }
    
    /**
     * Helper function to coerce browsers to reload js files when changed.
     *
     * @param string $_file
     * @return string file
     */
    public static function _appendFileTime( $_file )
    {
        $path = dirname(__FILE__). "/../../../$_file";
        return "$_file?". filectime($path);
    }
}