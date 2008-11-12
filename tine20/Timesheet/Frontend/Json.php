<?php
/**
 * Tine 2.0
 * @package     Timesheet
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 *
 * This class handles all Json requests for the Timesheet application
 *
 * @package     Timesheet
 * @subpackage  Frontend
 */
class Timesheet_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Timesheet';
    }
}
