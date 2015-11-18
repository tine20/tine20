<?php
/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Courses')->getId(),
            'model'             => 'Courses_Model_CourseFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Courses_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All courses", // _("All courses")
            'filters'           => array(
                array(
                    'field'     => 'is_deleted',
                    'operator'  => 'equals',
                    'value'        => '0'
            )),
        ))));
    }
    
    /**
     * init department
     * 
     * @see 0010554: create default department (school) on Courses installation
     */
    public static function _initializeDepartment()
    {
        // create a default department if none exists
        $departments = Tinebase_Department::getInstance()->getAll();
        if (count($departments) === 0) {
            $translation = Tinebase_Translation::getTranslation('Courses');
            $school = new Tinebase_Model_Department(array(
                'name' => $translation->_('School'),
                'description' => $translation->_('Defaul school for Courses application'),
            ));
            Tinebase_Department::getInstance()->create($school);
        }
    }
}
