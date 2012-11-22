<?php
/**
 * Inventory xls generation class
 *
 * @package     Inventory
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Inventory xls generation class
 * 
 * @package     Inventory
 * @subpackage  Export
 */
class Inventory_Export_Xls extends Tinebase_Export_Spreadsheet_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Inventory';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'i_default_xls';
}
