<?php
/**
 * Doc sheet export generation class
 *
 * Export into specific doc template
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar DocSheet generation class
 *
 * @package     Calendar
 * @subpackage  Export
 *
 */
class Calendar_Export_DocSheet extends Tinebase_Export_Richtext_Doc
{
    use Calendar_Export_GenericTrait;

    protected $_defaultExportname = 'cal_default_doc_sheet';

    protected $_daysEventMatrix = array();

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

//        $this->_records = new Tinebase_Record_RecordSet('Calendar_Model_Event');

        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * export single record
     */
    public function processRecord($record, $idx)
    {
        // progressive rendering is not possible with this advanced layout
        // so we split into days at this stage only
        $dayString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($record->dtstart, null, null, $this->_config->dateformat);
        $this->_daysEventMatrix[$dayString][] = $record;
    }

    /**
     * do the rendering
     *
     * @param array $result
     */
    protected function _onAfterExportRecords($result)
    {
        $templateProcessor = $this->getDocument();

        // first step: generate layout
        $dayblock = $templateProcessor->cloneBlock('DAYBLOCK', 1, false);
        $seperator = $templateProcessor->cloneBlock('SEPARATOR', 1, false);

        $dayCount = count($this->_daysEventMatrix);
        $daysblock = $dayCount ? $dayblock : '';
        for ($i=1; $i<$dayCount; $i++) {
            $daysblock .= $seperator;
            $daysblock .= $dayblock;
        }

        $templateProcessor->replaceBlock('DAYBLOCK', $daysblock);
        $templateProcessor->deleteBlock('SEPARATOR');

        // second step: render events
        foreach ($this->_daysEventMatrix as $dayString => $dayEvents) {
            $this->processDay($dayString, $dayEvents);

        }

        // third step: render generics
        if ($this->_from instanceof Tinebase_DateTime) {
            $templateProcessor->setValue('from', Tinebase_Translation::dateToStringInTzAndLocaleFormat($this->_from, null, null, $this->_config->dateformat));
        }
        if ($this->_until instanceof Tinebase_DateTime) {
            $templateProcessor->setValue('until', Tinebase_Translation::dateToStringInTzAndLocaleFormat($this->_until->getClone()->subSecond(1), null, null, $this->_config->dateformat));
        }

        parent::_onAfterExportRecords($result);
    }

    public function processDay($dayString, $dayEvents)
    {
        $templateProcessor = $this->getDocument();

        $templateProcessor->setValue('day', $dayString, 1);
        $cloneRow = $this->_config->columns->column->{0}->header;
        $templateProcessor->cloneRow($cloneRow, count($dayEvents));

        $this->processDayEvents($dayEvents);
    }

    public function processDayEvents($dayEvents)
    {
        foreach($dayEvents as $idx => $event) {
            parent::processRecord($event, $idx);
        }
    }
}