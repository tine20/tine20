<?php
/**
 * Tinebase Ods generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Ods generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Ods extends Tinebase_Export_Xls
{
    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'ods';


    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        $this->_excelVersion = 'Ods';
    }

    public static function getDefaultFormat()
    {
        return 'ods';
    }

    /**
     * get export content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }
}