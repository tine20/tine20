<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Calendar
 */
class Calendar_Export_VCalendarReport extends Tinebase_Export_Report_Abstract
{
    protected $_defaultExportname = 'cal_default_vcalendar_report';
    protected $_format = 'ics';
    protected $_exportClass = Calendar_Export_VCalendar::class;

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/calendar';
    }

    /**
     * @return array
     */
    public static function getPluginOptionsDefinition()
    {
        return [
            // Containers
            'sources' => [
                'label' => 'Containers to export', // _('Containers to export')
                'type' => 'containers',
                'config' => [
                    'recordClassName' => Calendar_Model_Event::class,
                    // TODO needed?
//                    'controllerClassName'           => Tinebase_Record_Path::class,
//                    'filterClassName'               => Tinebase_Model_PathFilter::class,
                ],
                // TODO add validation?
            ],
            // FileLocation
            'target' => [
                'label' => 'Export target', // _('Export target')
                'type' => 'filelocation',
            ]
        ];
    }
}
