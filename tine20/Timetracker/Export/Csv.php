<?php
/**
 * Timetracker Timesheet csv generation class
 *
 * @package     Timetracker
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Timetracker Timesheet csv generation class
 * 
 * @package     Timetracker
 * @subpackage    Export
 * 
 */
class Timetracker_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Timetracker_Model_Timesheet';
}
