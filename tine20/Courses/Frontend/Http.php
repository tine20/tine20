<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Http.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */

/**
 * This class handles all Http requests for the Courses application
 *
 * @package     Courses
 * @subpackage  Frontend
 */
class Courses_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Courses';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Courses/js/Models.js',
            'Courses/js/Courses.js',
            //'Courses/js/MainScreen.js',
            'Courses/js/CourseGridPanel.js',
            'Courses/js/CourseEditDialog.js',
        );
    }
}
