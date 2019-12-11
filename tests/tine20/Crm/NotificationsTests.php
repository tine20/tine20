<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Crm Notifications
 *
 * @package     Crm
 */
class Crm_NotificationsTests extends Crm_AbstractTest
{
    /**
     * @var Crm_Controller_Lead controller unter test
     */
    protected $_leadController;

    /**
     * (non-PHPdoc)
     * @see tests/tine20/Crm/AbstractTest::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        Tinebase_Config::getInstance()->clearCache();
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
            $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }

        $this->_leadController = Crm_Controller_Lead::getInstance();
    }

    /**
     * testNotification
     *
     * @return Crm_Model_Lead
     */
    public function testNotification()
    {
        self::flushMailer();
        $lead = $this->_getLead();
        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type' => 'CUSTOMER',
            'related_record' => $this->_getContact(),
            'own_model' => 'Crm_Model_Lead',
            'own_backend' => 'Sql',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => Tasks_Backend_Factory::SQL,
        ), TRUE));

        $savedLead = $this->_leadController->create($lead);

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertContains(' Lars Kneschke (Metaways', $bodyText);
        return $savedLead;
    }

    /**
     * testNotificationToResponsible
     */
    public function testNotificationToResponsible()
    {
        self::flushMailer();
        
        $lead = $this->_getLead();
        
        // give sclever access to lead container
        $this->_setPersonaGrantsForTestContainer($lead['container_id'], 'sclever');
        
        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type'                   => 'RESPONSIBLE',
            'related_record'         => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Tasks_Backend_Factory::SQL,
        ), TRUE));
        $this->_leadController->create($lead);
        
        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertContains('**PHPUnit **', $bodyText);
    }

    /**
     * testNoNotificationConfig
     */
   public function testNoNotificationConfigResponsible()
   {
       $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_RESPONSIBLE, 'RESPONSIBLE');
   }

    /**
     * @param string $configToSet
     * @param string $type
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
   protected function _notificationHelper($configToSet, $type)
   {
       Tinebase_Core::getPreference('Crm')->setValue(
           Crm_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS,
           Crm_Controller_Lead::NOTIFICATION_WITHOUT);

       self::flushMailer();

       $lead = $this->_getLead();

       $contact = $this->_getContact();
       $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);

       $lead->relations = array(new Tinebase_Model_Relation(array(
           'type' => $type,
           'related_record' => $savedContact,
           'own_model' => 'Crm_Model_Lead',
           'own_backend' => 'Sql',
           'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
           'related_model' => 'Addressbook_Model_Contact',
           'related_backend' => Tasks_Backend_Factory::SQL,
       ), TRUE));

       $this->_leadController->create($lead);

       $messages = self::getMessages();
       $this->assertEquals($type === 'RESPONSIBLE' ? 1 : 0, count($messages));
       self::flushMailer();
       Crm_Config::getInstance()->set($configToSet, $type === 'RESPONSIBLE' ? false : true);

       $lead['turnover'] = '100';

       try {
           $this->_leadController->update($lead);
           $messages = self::getMessages();
           $this->assertEquals($type === 'RESPONSIBLE' ? 0 : 1, count($messages));

           Crm_Config::getInstance()->set($configToSet, $type === 'RESPONSIBLE' ? true : false);
           Tinebase_Core::getPreference('Crm')->setValue(
               Crm_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS,
               'all');
       } catch (Tinebase_Exception_Record_NotDefined $ternd) {
           // FIXME sometime the relations get lost ... :(
           // maybe another test interfering with this one
       }
   }

    /**
     * testNoNotificationConfig
     */
    public function testNoNotificationConfigPartner()
    {
        $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_PARTNER, 'PARTNER');
    }

    /**
     * testNoNotificationConfig
     */
    public function testNoNotificationConfigCustomer()
    {
        $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_CUSTOMER, 'CUSTOMER');
    }

    /**
     * testNotificationToResponsible
     */
    public function testNotificationOnDelete()
    {
        $lead = $this->_getLead();

        // give sclever access to lead container
        $this->_setPersonaGrantsForTestContainer($lead['container_id'], 'sclever');

        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type' => 'RESPONSIBLE',
            'related_record' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
            'own_model' => 'Crm_Model_Lead',
            'own_backend' => 'Sql',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => Tasks_Backend_Factory::SQL,
        ), TRUE));
        $savedLead = $this->_leadController->create($lead);

        self::flushMailer();

        $this->_leadController->delete($savedLead->getId());

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertContains('**PHPUnit **', $bodyText);
    }

    /**
     * @see 0011694: show tags and history / latest changes in lead notification mail
     */
    public function testTagAndHistory()
    {
        $lead = $this->testNotification();

        self::flushMailer();

        $tag = new Tinebase_Model_Tag(array(
            'type' => Tinebase_Model_Tag::TYPE_SHARED,
            'name' => 'testNotificationTag',
            'description' => 'testNotificationTag',
            'color' => '#009B31',
        ));
        $lead->tags = array($tag);
        $lead->description = 'updated description';
        $this->_leadController->update($lead);

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = quoted_printable_decode($messages[0]->getBodyText()->getContent());

        $translate = Tinebase_Translation::getTranslation('Crm');
        $changeMessage = $translate->_("'%s' changed from '%s' to '%s'.");

        $this->assertContains("testNotificationTag\n", $bodyText);
        $this->assertContains(sprintf($changeMessage, 'description', 'Description', 'updated description'), $bodyText);
    }
    
    /**
     * get contact
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
}
