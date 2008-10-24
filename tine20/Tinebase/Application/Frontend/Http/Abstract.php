<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http interface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Frontend_Http_Abstract extends Tinebase_Application_Frontend_Abstract implements Tinebase_Application_Frontend_Http_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        $standardFile = "/{$this->_appname}/js/{$this->_appname}.js";
        if (file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . $standardFile)) {
            return array($standardFile);
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
        $standardFile = "/{$this->_appname}/css/{$this->_appname}.css";
        if (file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . $standardFile)) {
            return array($standardFile);
        }
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
        $path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . "/$_file";
        return "$_file?". @filectime($path);
    }
}