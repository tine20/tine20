<?php
/**
 * HumanResources Ods generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * HumanResources Ods generation class
 * 
 * @package     HumanResources
 * @subpackage  Export
 * 
 */
class HumanResources_Export_Ods extends Tinebase_Export_Spreadsheet_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'HumanResources';

    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'hr_default_ods';
}
