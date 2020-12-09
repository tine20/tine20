<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Felamimail_Model_MessageTest
 */
class Felamimail_Model_MessagePipeTest extends Felamimail_TestCase
{
    /**
     * test create duplicated folder from pipeline
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCreateDuplicatedFolder()
    {
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'spam'
                    ]
                ]
            ]
        ];

        $pipe = 'spam';

        // move to root
        $this->_messagePipeTestHelper($config[$pipe]);
        $this->_messagePipeTestHelper($config[$pipe]);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $this->_messagePipeTestHelper($config[$pipe]);
        $this->_messagePipeTestHelper($config[$pipe]);
    }
    
    /**
     * test message pipe spam/ham with copy mail
     * - copy mail to spam/ham folder in internal account
     * - copy mail to sub folder in internal account
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToInternalAccount()
    {
        // create shared account
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'ham'
                    ]
                ]
            ]
        ];

        // move to root

        $pipe = 'spam';

        $message = $this->_messagePipeTestHelper($config[$pipe]);
        $this->_assertMessageInFolder('spam', $message['subject']);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $message = $this->_messagePipeTestHelper($config[$pipe]);
        $this->_assertMessageInFolder('INBOX.SPAM', $message['subject'] );

        $pipe = 'ham';

        $message = $this->_messagePipeTestHelper($config[$pipe]);
        $this->_assertMessageInFolder('ham', $message['subject']);

        $config[$pipe]['config']['target']['folder'] = 'INBOX/HAM';
        $message = $this->_messagePipeTestHelper($config[$pipe]);
        $this->_assertMessageInFolder('INBOX.HAM', $message['subject'] );
    }

    /**
     * test message pipe spam/ham with copy mail
     * - copy mail to spam/ham folder in shared account
     * - copy mail to sub folder in shared account
     * 
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToAnotherAccount()
    {
        // create shared account
        $account = $this->_createSharedAccount();

        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'ham'
                    ]
                ]
            ]
        ];

        // move to root
        $pipe = 'spam';

        $message = $this->_messagePipeTestHelper($config[$pipe], $account);
        $this->_assertMessageInFolder('spam', $message['subject'], $account);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $message = $this->_messagePipeTestHelper($config[$pipe], $account);
        $this->_assertMessageInFolder('INBOX.SPAM', $message['subject'], $account);

        $pipe = 'ham';

        $message = $this->_messagePipeTestHelper($config[$pipe], $account);
        $this->_assertMessageInFolder('ham', $message['subject']);

        $config[$pipe]['config']['target']['folder'] = 'INBOX/HAM';
        $message = $this->_messagePipeTestHelper($config[$pipe], $account);
        $this->_assertMessageInFolder('INBOX.HAM', $message['subject'], $account);
    }

    /**
     * test message pipe spam/ham with copy mail to local directory
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToLocalDir()
    {
        $this->_testNeedsTransaction();
        $tmp = Tinebase_Core::getTempDir();

        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/ham'
                    ]
                ]
            ]
        ];

        // send message and copy to spam dir
        $message = $this->_messagePipeTestHelper($config['spam']);
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/spam'
        self::assertTrue(is_dir($tmp . '/spam'), 'no spam dir found');
        $filename = $tmp . '/spam/' . $message->headers['message-id'] . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);

        // send message and copy to ham dir
        $message = $this->_messagePipeTestHelper($config['ham']);
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/ham'
        self::assertTrue(is_dir($tmp . '/ham'), 'no ham dir found');
        $filename = $tmp . '/ham/' . $message->headers['message-id'] . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);
    }

    /**
     * test message pipe spam with move mail
     * - move mail configured trash folder of current user
     * - delete original message
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeMove()
    {
        $pipe = 'spam';
        $targetFolder = 'trash';
        $config = [
            'spam' => [
                'strategy' => 'move',
                'config' => [
                    'target' => [
                        'folder' => '#trash' ,
                    ]
                ]
            ]
        ];

        $inbox = $this->_getFolder('INBOX');
        $inboxBefore = $this->_json->updateMessageCache($inbox['id'], 30);

        $message = $this->_messagePipeTestHelper($config[$pipe]);
        $inboxAfter = $this->_getFolder('INBOX');

        $this->assertEquals($inboxBefore['cache_unreadcount'], $inboxAfter['cache_unreadcount']);
        $this->assertEquals($inboxBefore['cache_totalcount'], $inboxAfter['cache_totalcount']);

        $this->_assertMessageInFolder($targetFolder, $message['subject']);
    }

    /**
     * test message pipe ham with rewrite subject
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeRewriteSubject()
    {
        $pipe = 'ham';
        $config = [
            'ham' => [
                'strategy' => 'rewrite_subject',
                'config' => [
                    'pattern' => '/^SPAM\? \(.+\) \*\*\* /',
                    'replacement' => '',
                ]
            ]
        ];

        $this->_messagePipeTestHelper($config[$pipe]);
        $this->_assertMessageInFolder('INBOX', 'test messagePipe');
    }

    /**
     * message pipe helper function
     * - set spam strategy config
     * - appends message from created messages
     * - adds appended message to cache for move/copy strategy
     * - execute pipeLine
     *
     * @param array $_config
     * @param string $_folderName
     * @param Felamimail_Model_Account|null $_account
     *
     * @return Felamimail_Model_Message|NULL
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     */
    public function _messagePipeTestHelper($_config, $_account = null)
    {
        // set spam strategy config
        $this->_setFeatureForTest(Felamimail_Config::getInstance(), Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');
        $config = [
            'pattern' => '/^SPAM\? \(.+\) \*\*\* /',
        ];
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG, $config);

        $this->_getFolder('INBOX', true, $_account);
        $this->_foldersToClear[] = ['INBOX', 'Sent', 'Trash'];
        $subject = 'SPAM? (15) *** test messagePipe';

        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            $subject);

        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);

        // create and execute pipeLine
        $pipeLineRecord = Felamimail_Model_MessagePipeConfig::factory($_config);
        $rs = new Tinebase_Record_RecordSet(Felamimail_Model_MessagePipeConfig::class);
        $rs->addRecord(new Felamimail_Model_MessagePipeConfig([
            Felamimail_Model_MessagePipeConfig::FLDS_CLASSNAME => get_class($pipeLineRecord),
            Felamimail_Model_MessagePipeConfig::FLDS_CONFIG_RECORD => $pipeLineRecord]));

        $pipeLine = new Tinebase_BL_Pipe($rs);
        $pipeLine->execute($message);
        
        return $message;
    }
}
