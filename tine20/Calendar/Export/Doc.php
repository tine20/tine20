<?php
/**
 * Doc sheet export generation class
 *
 * Export into specific doc template
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar DocSheet generation class
 *
 * @package     Calendar
 * @subpackage  Export
 *
 */
class Calendar_Export_Doc extends Tinebase_Export_Doc
{
    use Calendar_Export_GenericTrait;

    protected $_defaultExportname = 'cal_default_doc_sheet';

    protected $_daysEventMatrix = array();

    protected $_cloneRow = null;

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
     * export single record
     * @param Tinebase_Record_Interface $record
     */
    public function processRecord(Tinebase_Record_Interface $record)
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

        $this->_loadTwig();
        $this->_hasTemplate = true;
        $this->_dumpRecords = false;

        // first step: generate layout
        $dayblock = $templateProcessor->cloneBlock('DAYBLOCK', 1, false);
        $seperator = $templateProcessor->cloneBlock('SEPARATOR', 1, false);

        if (preg_match('/<w:tbl.*\${([^}]+)}/is', $dayblock, $matches)) {
            $this->_cloneRow = $matches[1];
        } else {
            throw new Tinebase_Exception_UnexpectedValue('DAYBLOCK needs to contain a replacement variable');
        }

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

        parent::_onAfterExportRecords($result);
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        return array_merge(parent::_getTwigContext($context), array(
            'calendar'      => array(
                'from'          => $this->_from,
                'until'         => $this->_until,
            )
        ));
    }

    public function processDay($dayString, $dayEvents)
    {
        $templateProcessor = $this->getDocument();

        $templateProcessor->setValue('day', $dayString, 1);
        $templateProcessor->cloneRow($this->_cloneRow, count($dayEvents));

        $this->processDayEvents($dayEvents);
    }

    public function processDayEvents($dayEvents)
    {
        $this->_rowCount = 0;
        foreach($dayEvents as $idx => $event) {
            ++$this->_rowCount;
            parent::_processRecord($event);
        }
    }
}