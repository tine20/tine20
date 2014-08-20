<?php
/**
 * Tine 2.0
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * CalDAV import helper functions for CLI
 *
 * @package     Tasks
 */
class Tasks_Frontend_CalDAV_Cli extends Calendar_Frontend_CalDAV_Cli
{
    protected $_caldavClientClass = 'Tasks_Import_CalDav_Client';
    protected $_numberOfImportRuns = 1;
    protected $_appName = 'Tasks';
}
