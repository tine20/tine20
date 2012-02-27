<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class Courses_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * @return void
     */
    public function update_0()
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
        'value'=> '0'

                    )),
            
            )                
        )));
        
        $this->setApplicationVersion('Courses', '5.1');
    }    
    
}
