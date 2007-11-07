<?php

/**
 * Interface class for an EGW2.0 application with Http interface
 * 
 * Note, that the Http inerface in egw 2.0 is used to generate the base layouts
 * in new browser windows.
 * 
 * Each egw application must extend this class to gain an native egw2.0 user
 * interface.
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Interface Egwbase_Application_Http_Interface extends Egwbase_Application_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array of filenames
     */
    public function getJsFilesToInclude();
    
    /**
     * Retruns all CSS files which must be inclued for this app
     *
     * @return array of filenames
     */
    public function getCssFilesToInclude();
    
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
    public function getInitialData();
    
}