<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - increase size of n_family and org_name
     */
    public function update_0()
    {
        // remove this index as it gets to long and we do not need it
        $this->_backend->dropIndex('addressbook', 'org_name-n_family-n_given');
        
        $colsToChange = array('n_family', 'org_name');
        foreach ($colsToChange as $col) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>' . $col . '</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->alterCol('addressbook', $declaration);
        }

        $this->setTableVersion('addressbook', 13);
        $this->setApplicationVersion('Addressbook', '5.1');
    }

    /**
     * update to 5.2
     * - enum -> text
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>gender</name>
                <type>text</type>
                <length>32</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('addressbook_salutations', $declaration);
        $this->setTableVersion('addressbook_salutations', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <notnull>true</notnull>
                <default>list</default>
            </field>');
        $this->_backend->alterCol('addressbook_lists', $declaration);
        $this->setTableVersion('addressbook_lists', 2);
        
        $this->setApplicationVersion('Addressbook', '5.2');
    }
    
    /**
     * update to 5.3
     * - rename cols lon, lat -> adr_one_lon, adr_one_lat
     * - add cols adr_two_lon, adr_two_lat
     * - delete addressbook config we don't need anymore
     */
    public function update_2()
    {
    	$declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>adr_one_lon</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'lon');
    	
    	$declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>adr_one_lat</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration, 'lat');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>adr_two_lon</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>'
		);
        $this->_backend->addCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>adr_two_lat</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>'
		);
        $this->_backend->addCol('addressbook', $declaration);
        
        $this->setTableVersion('addressbook', 14);
        
        // delete config we don't need anymore
        Tinebase_Config::getInstance()->deleteConfigForApplication(Tinebase_Config::APPDEFAULTS, 'Addressbook');
    	
    	$this->setApplicationVersion('Addressbook', '5.3');
    }

    /**
     * update to 5.4
     * - remove unused index (org_name-n_family-n_given)
     */
    public function update_3()
    {
        // remove this index as it gets to long and we do not need it
        try {
            $this->_backend->dropIndex('addressbook', 'org_name-n_family-n_given');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already removed
        }

        $this->setTableVersion('addressbook', 15);
        $this->setApplicationVersion('Addressbook', '5.4');
    }
    
    /**
     * update to 5.5
     * - update import export defs
     *
     * @return void
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        $this->setApplicationVersion('Addressbook', '5.5');
    }

    /**
     * update to 5.6
     * - convert salutations to keyfield config
     *
     * @return void
     */
    public function update_5()
    {
        $addressbookAppId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();
        
        // alter salutation_id -> salutation
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>salutation</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        
        $this->_backend->alterCol('addressbook', $declaration, 'salutation_id');
        
        // get all current salutation datas and drop old salutation table
        $stmt = $this->_db->query("SELECT * FROM `" . SQL_TABLE_PREFIX . "addressbook_salutations`");
        $salutationDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $this->_backend->dropTable('addressbook_salutations', $addressbookAppId);
        
        // update addressbook table
        $salutationMap = array(); // oldId => newId
        foreach ($salutationDatas as $salutationData) {
            $salutationMap[$salutationData['id']] = $salutationData['name'];
            $this->_db->update(SQL_TABLE_PREFIX . 'addressbook', array(
                'salutation' => strtoupper($salutationData['name']),
            ), "`salutation` = '{$salutationData['id']}'");
        }
        
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
    
        $keyfieldConfig = array(
            'name'    => Addressbook_Config::CONTACT_SALUTATION,
            'records' => array(
                array('id' => 'MR',      'value' => 'Mr', 	   'gender' => Addressbook_Model_Salutation::GENDER_MALE,   'image' => 'images/empty_photo_male.png',    'system' => true), //_('Mr')
                array('id' => 'MS',      'value' => 'Ms',      'gender' => Addressbook_Model_Salutation::GENDER_FEMALE, 'image' => 'images/empty_photo_female.png',  'system' => true), //_('Ms')
                array('id' => 'COMPANY', 'value' => 'Company', 'gender' => Addressbook_Model_Salutation::GENDER_OTHER,  'image' => 'images/empty_photo_company.png', 'system' => true), //_('Company')
            ),
        );
    
        // add non system custom salutation
        foreach ($salutationDatas as $salutationData) {
            if (! in_array(strtoupper($salutationData['name']), array('MR', 'MS', 'COMPANY'))) {
                $keyfieldConfig['records'][] = array('id' => strtoupper($salutationData['name']), 'value' => $salutationData['name'], 'gender' => $salutationData['gender'], 'image' => $salutationData['image_path']);
            }
        }

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Addressbook_Config::CONTACT_SALUTATION,
            'value'             => json_encode($keyfieldConfig),
        )));
        
        $this->setTableVersion('addressbook', '16');
        $this->setApplicationVersion('Addressbook', '5.6');
    }
}
