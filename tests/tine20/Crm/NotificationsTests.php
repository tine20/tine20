<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
            'type'                   => 'CUSTOMER',
            'related_record'         => $this->_getContact(),
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Tasks_Backend_Factory::SQL,
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
     * testNotificationToResponsible
     */
    public function testNotificationOnDelete()
    {
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
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'testNotificationTag',
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
}
