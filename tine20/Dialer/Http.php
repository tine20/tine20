<?php
/**
 * Tine 2.0
 * 
 * @package     Dialer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the dialer application
 * 
 * @package Dialer
 */
class Dialer_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Dialer';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Dialer/js/Dialer.js'
        );
    }
}