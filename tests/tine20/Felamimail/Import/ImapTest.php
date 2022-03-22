<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Felamimail_Import_Imap
 */
class Felamimail_Import_ImapTest extends TestCase
{
    /**
     * @var Tinebase_Model_ImportExportDefinition
     */
    protected $_definition;

    /**
     * @var Felamimail_Controller_MessageTest
     */
    protected Felamimail_Controller_MessageTest $_emailTestClass;

    /**
     * set up test environment
     *
     * @todo move setup to abstract test case
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
        Tinebase_ImportExportDefinition::getInstance()->delete([$this->_definition->getId()]);
        parent::tearDown();
    }

    protected function _createDefinition()
    {
        $account = $this->_emailTestClass->getAccount();
        $account->resolveCredentials(false);

        $username = $account->user;
        $password = $account->password;

        $definitionName = 'testFelamimailImportImap';
        try {
            $this->_definition = Tinebase_ImportExportDefinition::getInstance()->getByName($definitionName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->_definition = Tinebase_ImportExportDefinition::getInstance()->create(new Tinebase_Model_ImportExportDefinition(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
                'name' => $definitionName,
                'type' => 'import',
                'model' => 'Addressbook_Model_Contact',
                'plugin' => Felamimail_Import_Imap::class,
                'plugin_options' => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <dryrun>0</dryrun>
                <folder>Junk</folder>
                <host>' . $account->host . '</host>
                <port>' . $account->port . '</port>
                <ssl>' . $account->ssl . '</ssl>
                <user>' . $username . '</user>
                <password>' . $password . '</password>
            </config>'
            )));
        }
    }

    public function testImport()
    {
        $this->_testNeedsTransaction();
        $failFilename = 'import_contact_fail.eml';
        $result = $this->_import(['import_contact.eml', $failFilename]);

        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        self::assertEquals(1, $result['failcount'], print_r($result, true));
        $contact = $result['results']->getFirstRecord();
        self::assertEquals('Setz', $contact->n_fn, print_r($contact->toArray(), true));

        // check if failed import is marked FLAGGED
        $message = $this->_emailTestClass->searchAndCacheMessage($failFilename);
        $expected = [
            Zend_Mail_Storage::FLAG_SEEN,
            Zend_Mail_Storage::FLAG_FLAGGED,
        ];
        $flags = $message->flags;
        sort($expected);
        sort($flags);
        self::assertEquals($expected, $flags, 'message not FLAGGED: ' . print_r($message->toArray(), true));

//        $importer = Felamimail_Import_Imap::createFromDefinition($this->_definition);
//        $result = $importer->import();
//        self::assertEquals(0, $result['totalcount'],
//            'should no longer import a message - imported message should have been marked as seen');
    }

    protected function _import($emailFiles)
    {
        $this->_createDefinition();
        $importer = Felamimail_Import_Imap::createFromDefinition($this->_definition);

        // put messages with adb json into mailaccount Junk folder
        foreach ($emailFiles as $emailFile) {
            $message = $this->_emailTestClass->messageTestHelper(
                $emailFile
            );
            // remove seen flag - importer only imports "unseen" messages
            Felamimail_Controller_Message_Flags::getInstance()->clearFlags($message, [Zend_Mail_Storage::FLAG_SEEN]);
        }

        return $importer->import();
    }
}
