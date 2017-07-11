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
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        $tz = Tinebase_Core::getUserTimezone();
        return array_merge(parent::_getTwigContext($context), array(
            'calendar'      => array(
                'from'          => null !== $this->_from ? $this->_from->getClone()->setTimezone($tz) : null,
                'until'         => null !== $this->_until ? $this->_until->getClone()->setTimezone($tz) : null,
            )
        ));
    }
}