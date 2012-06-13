<?php
/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        ))));
    }
    
    /**
    * init key fields
    */
    protected function _initializeKeyFields()
    {
        self::createInternetAccessKeyfield();
    }
    
    /**
     * create INTERNET_ACCESS keyfield
     */
    public static function createInternetAccessKeyfield()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
    
        $keyfieldConfig = array(
            'name'    => Courses_Config::INTERNET_ACCESS,
            'records' => array(
                array('id' => 'ON',       'value' => 'On',       'image' => 'images/oxygen/16x16/actions/dialog-apply.png',   'system' => true), //_('On')
                array('id' => 'OFF',      'value' => 'Off',      'image' => 'images/oxygen/16x16/actions/dialog-cancel.png',  'system' => true), //_('Off')
                array('id' => 'FILTERED', 'value' => 'Filtered', 'image' => 'images/oxygen/16x16/actions/view-choose.png',    'system' => true), //_('Filtered')
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Courses')->getId(),
            'name'              => Courses_Config::INTERNET_ACCESS,
            'value'             => json_encode($keyfieldConfig),
        )));
    }
}
