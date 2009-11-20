<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * @package     Calendar
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the calendar application
 *
 * @package     Calendar
 * @subpackage  Server
 */
class Calendar_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Calendar';
    
    public function getJsFilesToInclude()
    {
        return array(
            'Calendar/js/ParallelEventsRegistry.js',
            'Calendar/js/Model.js',
            'Calendar/js/AdminPanel.js',
            'Calendar/js/CalendarPanel.js',
            'Calendar/js/EventUI.js',
            'Calendar/js/EventSelectionModel.js',
            'Calendar/js/DaysView.js',
            'Calendar/js/MonthView.js',
            'Calendar/js/Calendar.js',
            'Calendar/js/PagingToolbar.js',
            'Calendar/js/EventDetailsPanel.js',
            'Calendar/js/MainScreenCenterPanel.js',
            'Calendar/js/MainScreenLeftPanel.js',
            'Calendar/js/EventEditDialog.js',
            'Calendar/js/AttendeeGridPanel.js',
            'Calendar/js/CalendarSelectTreePanel.js',
            'Calendar/js/CalendarSelectWidget.js',
            'Calendar/js/ColorManager.js',
            'Calendar/js/RrulePanel.js',
            'Calendar/js/ResourcesGridPanel.js',
            'Calendar/js/ResourceEditDialog.js',
        );
    }
    
    public function getCssFilesToInclude()
    {
        return array(
            'Calendar/css/daysviewpanel.css',
            'Calendar/css/monthviewpanel.css',
            'Calendar/css/Calendar.css'
        );
    }
}