<?php
/**
 * Sales Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
abstract class Sales_Export_Ods_Abstract extends Tinebase_Export_Spreadsheet_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Sales';
}
