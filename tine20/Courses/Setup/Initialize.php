<?php
/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Courses initialization
 * 
 * @package     Setup
 */
class Courses_Setup_Initialize extends Setup_Initialize
{
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
                    'account_id'        => NULL,
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Courses')->getId(),
                    'model'             => 'Courses_Model_CourseFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
                    'name'              => Courses_Preference::DEFAULTPERSISTENTFILTER_NAME,
                    'description'       => "All courses", // _("All courses")
                    'filters'           => array(
                        array(
                            'field'     => 'is_deleted',
                            'operator'  => 'equals',
                            'value'        => '0'
                    )),
            )
        ))); 
    }
}
