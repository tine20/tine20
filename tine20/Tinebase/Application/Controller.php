<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * generic controller for applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Application_Controller extends Tinebase_Controller_Abstract
{
    /**
     * Tinebase_Controller_Abstract constructor.
     *
     * @param string $applicationName
     */
    public function __construct($applicationName)
    {
        $this->_applicationName = $applicationName;
    }

    /**
     * Instance of Controller Object.
     */
    public static function getInstance()
    {
        throw new Tinebase_Exception('Use the constructor');
    }
}
