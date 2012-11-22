<?php
/**
 * Inventory Ods generation class
 *
 * @package     Inventory
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Inventory Ods generation class
 * 
 * @package     Inventory
 * @subpackage    Export
 * 
 */
class Inventory_Export_Ods extends Tinebase_Export_Spreadsheet_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Inventory';

    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'i_default_ods';
}
