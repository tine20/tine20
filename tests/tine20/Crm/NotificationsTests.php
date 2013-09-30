<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * @var Zend_Mail_Transport_Array
     */
    protected static $_mailer = NULL;
    
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
     * testInvitation
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
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Tasks_Backend_Factory::SQL,
        ), TRUE));
        $this->_leadController->create($lead);
        
        $messages = $this->getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertContains(' Lars Kneschke (Metaways', $bodyText);
    }
    
    /**
     * get messages
     * 
     * @return array
     * 
     * @todo move this to TestServer?
     */
    public function getMessages()
    {
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
        }
        
        return self::getMailer()->getMessages();
    }
    
    /**
     * get mailer
     * 
     * @return Zend_Mail_Transport_Abstract
     * 
     * @todo move this to TestServer?
     */
    public static function getMailer()
    {
        if (! self::$_mailer) {
            self::$_mailer = Tinebase_Smtp::getDefaultTransport();
        }
        
        return self::$_mailer;
    }
    
    /**
     * flush mailer (send all remaining mails first)
     * 
     * @todo move this to TestServer?
     */
    public static function flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        self::getMailer()->flush();
    }
}
