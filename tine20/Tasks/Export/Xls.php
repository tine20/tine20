<?php
/**
 * Tasks xls generation class
 *
 * @package     Tasks
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Tasks xls generation class
 * 
 * @package     Tasks
 * @subpackage  Export
 */
class Tasks_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Tasks';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'tasks_default_xls';
}
