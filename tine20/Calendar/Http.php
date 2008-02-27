<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the calendar application
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Calendar_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Calendar';
    
    public function getJsFilesToInclude()
    {
        foreach( ( $files = array(
            'Calendar.js',
            'GridView_Days.js'
        ) ) as $key => $file) {
            $files[$key] = 'Calendar/js/' . self::_appendFileTime($file);
        }
        return $files;
        
    }
    
    public function getCssFilesToInclude()
    {
        return array(
            'Crm/css/Eventscheduler.css',
            'Calendar/css/Calendar.css'
        );
    }
}