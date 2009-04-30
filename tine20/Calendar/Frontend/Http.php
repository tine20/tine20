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
            'Calendar/js/CalendarPanel.js',
            'Calendar/js/DaysView.js',
            'Calendar/js/Calendar.js',
        );
    }
    
    public function getCssFilesToInclude()
    {
        return array(
            'Calendar/css/daysviewpanel.css',
            'Calendar/css/Calendar.css'
        );
    }
}