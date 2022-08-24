<?php
/**
 * Projects xls generation class
 *
 * @package     Projects
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Projects xls generation class
 * 
 * @package     Projects
 * @subpackage  Export
 */
class Projects_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Projects';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'projects_default_xls';
}
