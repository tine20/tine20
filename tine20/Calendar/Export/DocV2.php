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
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar DocSheet generation class
 *
 * @package     Calendar
 * @subpackage  Export
 *
 */
class Calendar_Export_DocV2 extends Tinebase_Export_DocV2
{
    use Calendar_Export_GenericTrait;

    protected $_defaultExportname = 'cal_default_doc_sheet';

    protected $_daysEventMatrix = array();

    protected $_cloneRow = null;
    
    protected $_calAttendeeCustomFResolveRAII;

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
        $this->_calAttendeeCustomFResolveRAII = new Tinebase_RAII(function (){
            Calendar_Model_Attender::setContactCustomfieldResolve(false);
        });
        Calendar_Model_Attender::setContactCustomfieldResolve(true);
    }

    public function _processRecord(Tinebase_Record_Interface $record)
    {
        if ($record instanceof Calendar_Model_Event) {
            $record->resolveOrganizer();
        }

        return parent::_processRecord($record);
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

    protected function _extendTwigSetup()
    {
        parent::_extendTwigSetup();

        $this->_twig->getEnvironment()->addFunction(new Twig_SimpleFunction('attendeeType', function ($attendee, $locale = null) {
            if ($locale !== null) {
                $locale = Tinebase_Translation::getLocale($locale);
            }

            if ($attendee instanceof Calendar_Model_Attender) {
                return $attendee->getType($locale);
            }
        }));

    }
}