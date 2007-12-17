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
 * Interface class for an EGW2.0 application with Http interface
 * 
 * Note, that the Http inerface in egw 2.0 is used to generate the base layouts
 * in new browser windows.
 * 
 * Each egw application must extend this class to gain an native egw2.0 user
 * interface.
 */
Interface Egwbase_Application_Http_Interface extends Egwbase_Application_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude();
    
    /**
     * Retruns all CSS files which must be inclued for this app
     *
     * @return array Array of filenames
     */
    public function getCssFilesToInclude();
    
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
    public function getInitialMainScreenData();
    
}