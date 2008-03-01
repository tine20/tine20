<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http inerface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Http_Abstract extends Tinebase_Application_Abstract implements Tinebase_Application_Http_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
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
     * @return array Array of filenames
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
     * When the mainScreen is created, Tinebase_Http_Controler queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial datas are just javascript varialbes declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * varialbe names get prefixed with Tine.<applicationname>
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
        return "$_file?". @filectime($path);
    }
}