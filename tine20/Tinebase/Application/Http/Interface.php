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
 * Interface class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http inerface in tine 2.0 is used to generate the base layouts
 * in new browser windows.
 * 
 * Each tine application must extend this class to gain an native tine 2.0 user
 * interface.
 * @package     Tinebase
 * @subpackage  Application
 */
Interface Tinebase_Application_Http_Interface extends Tinebase_Application_Interface
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
    
}