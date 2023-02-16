<?php
/**
 * Calendar xls generation class
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Calendar xls generation class
 * 
 * @package     Calendar
 * @subpackage  Export
 */
class Calendar_Export_CalendarXls extends Tinebase_Export_Xls
{
    use Calendar_Export_GenericTrait;

    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'calendar_default_xls';
}
