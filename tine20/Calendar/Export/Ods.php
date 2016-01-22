<?php
/**
 * Ods export generation class
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar Ods generation class
 *
 * @package     Calendar
 * @subpackage  Export
 *
 */
class Calendar_Export_Ods extends Tinebase_Export_Spreadsheet_Ods
{
    use Calendar_Export_GenericTrait;

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $this->init($_filter, $_controller, $_additionalOptions);

        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'cal_default_ods';
}
