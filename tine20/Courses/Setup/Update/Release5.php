<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
                    )
                ),
            )
        )));
        
        $this->setApplicationVersion('Courses', '5.1');
    }
    
    /**
    * update to 5.2
    * - internet column now is a textfield / keyfield
    * 
    * @return void
    */
    public function update_1()
    {
        Courses_Setup_Initialize::createInternetAccessKeyfield();
        
        // fetch old values and update courses
        $stmt = $this->_db->query("SELECT id,internet FROM `" . SQL_TABLE_PREFIX . "courses`");
        $internetAccess = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>internet</name>
                <type>text</type>
                <length>64</length>
            </field>');
        $this->_backend->alterCol('courses', $declaration);
        
        foreach ($internetAccess as $data) {
            $this->_db->update(SQL_TABLE_PREFIX . 'courses', array(
                'internet' => ($data['internet']) ? 'ON' : 'OFF',
            ), "`id` = '{$data['id']}'");
        }
        
        $this->setTableVersion('courses', 5);
        $this->setApplicationVersion('Courses', '5.2');
    }

    /**
    * update to 6.0
    */
    public function update_2()
    {
        $this->setApplicationVersion('Courses', '6.0');
    }
}
