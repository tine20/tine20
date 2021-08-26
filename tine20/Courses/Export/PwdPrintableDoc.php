<?php declare(strict_types=1);

/**
 * Courses pwd xls generation class
 *
 * @package     Courses
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Courses pwd xls generation class
 *
 * @package     Courses
 * @subpackage  Export
 */
class Courses_Export_PwdPrintableDoc extends Tinebase_Export_Doc2
{
    protected $_pwds;

    /**
     * the constructor
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_pwds
     * @param array $_additionalOptions (optional) additional options
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function __construct(Tinebase_Record_RecordSet $_records, array $_pwds, array $_additionalOptions = [])
    {
        $this->_pwds = $_pwds;
        $this->_records = $_records;
        $this->_config = new Zend_Config_Xml('<?xml version="1.0" encoding="UTF-8"?><config>
                <template>tine20:///Tinebase/folders/shared/export/templates/Courses/courses_pwd_export.docx</template>
            </config>', /* section = */ null, /* runtime mods allowed = */ true);
        parent::__construct(new Tinebase_Model_FullUserFilter(), null, $_additionalOptions);
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        if (isset($context['record']) && isset($this->_pwds[$context['record']->getId()])) {
            $context['pwd'] = $this->_pwds[$context['record']->getId()];
        }
        return parent::_getTwigContext($context);
    }
}
