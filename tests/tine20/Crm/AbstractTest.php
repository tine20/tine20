<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * abstract crm test class
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */
class Crm_AbstractTest extends TestCase
{
    /**
     * customfield name
     *
     * @var string
     */
    protected $_cfcName = null;

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        if ($this->_cfcName) {
            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication('Crm', $this->_cfcName);
            Tinebase_CustomField::getInstance()->deleteCustomField($cf);
        }
        parent::tearDown();
    }

    /**
     * get created contact
     *
     * @return Addressbook_Model_Contact
     */
    protected function _getCreatedContact()
    {
        return Addressbook_Controller_Contact::getInstance()->create($this->_getContact(), false);
    }

    /**
     * get UNcreated contact
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));
    }

    /**
     * get created task
     *
     * @return Tasks_Model_Task
     */
    protected function _getCreatedTask()
    {
        return Tasks_Controller_Task::getInstance()->create($this->_getTask());
    }

    /**
     * get UNcreated task
     * 
     * @return Tasks_Model_Task
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            //'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Tinebase_DateTime::now(),
            'percent'              => 70,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
    }
    
    /**
     * get lead
     *
     * @param boolean $addCf
     * @param boolean $addTags
     * @param boolean $mute
     * @param string $name
     * @return Crm_Model_Lead
     */
    protected function _getLead($addCf = false, $addTags = true, $mute = false, $name = 'PHPUnit LEAD')
    {
        if ($addCf) {
            $cfc = Tinebase_CustomFieldTest::getCustomField(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
                'model'          => 'Crm_Model_Lead',
                'name'           => 'testCustomField' . Tinebase_Record_Abstract::generateUID(5),
            ));
            $this->_cfcName = $cfc->name;

            $cfs = array(
                $this->_cfcName => '1234'
            );

            Tinebase_CustomField::getInstance()->addCustomField($cfc);
        } else {
            $cfs = array();
        }

        if ($addTags) {
            $tags = array(
                array('name' => 'lead tag', 'type' => Tinebase_Model_Tag::TYPE_SHARED)
            );
        } else {
            $tags = array();
        }

        return new Crm_Model_Lead(array(
            'lead_name'     => $name,
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => Tinebase_Container::getInstance()->getDefaultContainer(Crm_Model_Lead::class)->getId(),
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => NULL,
            'turnover'      => 200,
            'probability'   => 70,
            'end_scheduled' => NULL,
            'mute'          => $mute,
            'tags'          => $tags,
            'customfields'  => $cfs,
            'attachments'   => [],
        ));
    }


    /**
     * @param boolean $addCf
     * @param boolean $addTags
     * @param boolean $mute
     * @param string $name
     * @return array
     */
    protected function _getLeadArrayWithRelations($addCf = false, $addTags = true, $mute = false, $name = 'PHPUnit LEAD')
    {
        $contact    = $this->_getCreatedContact();
        $task       = $this->_getCreatedTask();
        $lead       = $this->_getLead($addCf, $addTags, $mute, $name);
        $product    = Sales_Controller_Product::getInstance()->create($this->_getProduct());
        $responsible = Addressbook_Controller_Contact::getInstance()->getContactByUserId(
            Tinebase_Core::getUser()->getId()
        );

        $price      = 200;

        $leadData = $lead->toArray();
        $leadData['relations'] = [
            [
                'type'  => 'RESPONSIBLE',
                'related_id' => $responsible->getId(),
                'remark' => [],
            ],
            array('type'  => 'TASK',    'related_id' => $task->getId()),
            array('type'  => 'PARTNER', 'related_id' => $contact->getId()),
            array('type'  => 'PRODUCT', 'related_id' => $product->getId(), 'remark' => array('price' => $price, 'quantity' => 3)),
        ];
        $note = array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',
        );
        $leadData['notes'] = array($note);

        return $leadData;
    }

    /**
     * get product
     *
     * @return Sales_Model_Product
     */
    protected function _getProduct()
    {
        return new Sales_Model_Product(array(
            'name'  => [[
                Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'en',
                Tinebase_Record_PropertyLocalization::FLD_TEXT => 'PHPUnit test product'
            ]],
            'price' => 10000,
            'gtin'  => 1234
        ));
    }

    /**
     * get lead filter
     * 
     * @return array
     */
    protected function _getLeadFilter()
    {
        return array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit'),
        );
    }
}
