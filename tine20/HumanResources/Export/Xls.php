<?php
/**
 * HumanResources xls generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * HumanResources xls generation class
 * 
 * @package     HumanResources
 * @subpackage  Export
 */
class HumanResources_Export_Xls extends Tinebase_Export_Spreadsheet_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'HumanResources';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'hr_default_xls';
}
