<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */


/**
 * Addressbook Doc generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_DocTest extends TestCase
{
    protected function _genericExportTest($_config)
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsql renders some small differences, so md5 checksum doesnt match. But the doc files look more or less ok?');
        }
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $definition = Tinebase_ImportExportDefinition::getInstance()
            ->updateOrCreateFromFilename($_config['definition'], $app);

        $config = new SimpleXMLElement($definition->plugin_options);
        $config->addChild('template', $_config['template']);
        $config->addChild('group', 'n_given');
        $definition->plugin_options = $config->asXML();
        Tinebase_ImportExportDefinition::getInstance()->update($definition);

        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomery',
            'n_given'           => 'Paul',
            'n_family'          => 'test',
            'email'             => 'tmp@test.de'
        )));
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomery')
        ));
        if (isset($_config['exportClass'])) {
            $class = $_config['exportClass'];
        } else {
            $class = 'Addressbook_Export_Doc';
        }
        /** @var Tinebase_Export_Doc $doc */
        $doc = new $class($filter, Addressbook_Controller_Contact::getInstance(), array(
            'definitionId' => $definition->getId(),
            'sortInfo' => array('field' => 'tel_work')
        ));
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), $_config['filename']) . '.docx';
        $doc->save($tempfile);

        $expectedFile = dirname($_config['template']) . '/results/' . basename($_config['template']);
        $contentHashIs = hash_file('md5', 'zip://' . $tempfile . '#word/document.xml');
        $contentHashToBe = hash_file('md5', 'zip' . substr($expectedFile, strpos($expectedFile, ':')) . '#word/document.xml');
        static::assertEquals($contentHashToBe, $contentHashIs, 'generated document does not match expectation');
    }

    public function testTableWithPOSTPmarkers()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_tableWithPOSTPmarkers.xml',
            'template' => 'file://' . __DIR__ . '/templates/tableWithPOSTPmarkers.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testRecordBlock()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_record_block.xml',
            'template' => 'file://' . __DIR__ . '/templates/record_block.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testSimpleTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_simple_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/simple_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupBlocks()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_group_blocks.xml',
            'template' => 'file://' . __DIR__ . '/templates/group_blocks.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupBlocksTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_group_blocks_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/group_blocks_with_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupedTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_grouped_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/grouped_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testDatasources()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_datasources.xml',
            'template' => 'file://' . __DIR__ . '/templates/datasources.docx',
            'filename' => __METHOD__ . '_',
            'exportClass' => 'Addressbook_Export_TestDocDataSource'
        ));
    }

    public function testContactFromFEData()
    {
        if (null === ($definition = Tinebase_ImportExportDefinition::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => Addressbook_Model_Contact::class,
                    'name' => 'adb_doc'
                ]))->getFirstRecord())) {
            static::markTestSkipped('adb_doc export definition required');
        }
        $jsonFEData = json_decode('{"recordData":{"container_id":"25f286ff20ef8b0b55b10e0758b4981c5b8a69ed","n_family":"lastName","n_given":"testFirname","n_middle":"","n_prefix":"","bday":"","org_name":"","org_unit":"","salutation":"MR","title":"","adr_one_street":"","adr_one_street2":"","adr_one_locality":"","adr_one_region":"","adr_one_postalcode":"","adr_one_countryname":"","adr_two_countryname":"","tel_work":"","tel_cell":"","tel_fax":"","tel_home":"","tel_fax_home":"","tel_cell_private":"","email":"","email_home":"","url":"","note":"","jpegphoto":"","tags":[],"industry":"","customfields":{},"relations":[{"own_backend":"Sql","related_backend":"Sql","own_id":0,"own_model":"Addressbook_Model_Contact","related_record":{"creation_time":"2018-03-26 15:16:27","created_by":{"accountId":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","accountLoginName":"vagrant","accountLastLogin":"2018-03-26 17:13:27","accountLastLoginfrom":"127.0.0.1","accountLastPasswordChange":"2018-03-23 14:12:17","accountStatus":"enabled","accountExpires":null,"accountPrimaryGroup":"f8ffe3a5dd8fae61de412e8a5093d1e0e039e528","accountHomeDirectory":null,"accountLoginShell":null,"accountDisplayName":"Admin Account, Tine 2.0","accountFullName":"Tine 2.0 Admin Account","accountFirstName":"Tine 2.0","accountLastName":"Admin Account","accountEmailAddress":"vagrant@example.org","lastLoginFailure":null,"loginFailures":"0","contact_id":"5a1d2ff7958ef36ae6a2cfa48c0f83550c841208","openid":null,"visibility":"displayed","created_by":"b9fe5b0b5e6c2acdeea0f590f4a830673c073138","creation_time":"2018-03-23 14:12:17","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_time":null,"deleted_by":null,"seq":"1","xprops":null,"container_id":{"id":"500247eb21c12e67407a2abe575925fd81d89185","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"4e7103e96c7722aa3c2218989df4891e88552a5e","content_seq":"6","created_by":null,"creation_time":null,"last_modified_by":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","last_modified_time":"2018-03-23 14:12:17","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","account_type":"user"},"path":"/shared/500247eb21c12e67407a2abe575925fd81d89185"}},"last_modified_time":"2018-03-26 15:16:27","last_modified_by":{"accountId":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","accountLoginName":"vagrant","accountLastLogin":"2018-03-26 17:13:27","accountLastLoginfrom":"127.0.0.1","accountLastPasswordChange":"2018-03-23 14:12:17","accountStatus":"enabled","accountExpires":null,"accountPrimaryGroup":"f8ffe3a5dd8fae61de412e8a5093d1e0e039e528","accountHomeDirectory":null,"accountLoginShell":null,"accountDisplayName":"Admin Account, Tine 2.0","accountFullName":"Tine 2.0 Admin Account","accountFirstName":"Tine 2.0","accountLastName":"Admin Account","accountEmailAddress":"vagrant@example.org","lastLoginFailure":null,"loginFailures":"0","contact_id":"5a1d2ff7958ef36ae6a2cfa48c0f83550c841208","openid":null,"visibility":"displayed","created_by":"b9fe5b0b5e6c2acdeea0f590f4a830673c073138","creation_time":"2018-03-23 14:12:17","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_time":null,"deleted_by":null,"seq":"1","xprops":null,"container_id":{"id":"500247eb21c12e67407a2abe575925fd81d89185","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"4e7103e96c7722aa3c2218989df4891e88552a5e","content_seq":"6","created_by":null,"creation_time":null,"last_modified_by":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","last_modified_time":"2018-03-23 14:12:17","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","account_type":"user"},"path":"/shared/500247eb21c12e67407a2abe575925fd81d89185"}},"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"2","container_id":{"id":"500247eb21c12e67407a2abe575925fd81d89185","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"4e7103e96c7722aa3c2218989df4891e88552a5e","content_seq":"6","created_by":null,"creation_time":null,"last_modified_by":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","last_modified_time":"2018-03-23 14:12:17","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"b5906f3ec6ef5494f2ed4ea6e78a9111a6011cda","account_type":"user"},"path":"/shared/500247eb21c12e67407a2abe575925fd81d89185"},"id":"7101a0595adaae16c6ea9755583a4fab2d226271","tid":"","private":"","cat_id":"","n_family":"McBlack","n_given":"James","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"James McBlack","n_fileas":"McBlack, James","bday":"","org_name":"Tine Publications, Ltd","org_unit":null,"salutation":"MR","title":null,"role":null,"assistent":null,"room":null,"adr_one_street":"Montgomery Street 589","adr_one_street2":null,"adr_one_locality":"Brighton","adr_one_region":"East Sussex","adr_one_postalcode":"BN1","adr_one_countryname":"GB","adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":"","adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":"+441273-3766-377","tel_cell":"+441273-24353676","tel_fax":"+441273-3766-16","tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":"+441273-335662","tel_fax_home":null,"tel_cell_private":"+441273-987643","tel_other":null,"tel_prefer":null,"email":"jmcblack@example.org","email_home":"full.house@mailforyouandme.uk","url":"","url_home":"","freebusy_uri":null,"calendar_uri":null,"note":"","tz":null,"pubkey":null,"jpegphoto":"images/empty_photo_male.png","account_id":"14c6d01f4a3f4796fedacbbd8546c938b3657f88","tags":[],"notes":"","customfields":"","attachments":"","paths":[{"id":"83f88cf0fa1ecc177d5d5e843826a2cb962d595e","path":"/Users/James McBlack","shadow_path":"/{Addressbook_Model_List}1fd276fddca4a59ee8455dddab2df7bcadab9ece/{Addressbook_Model_Contact}7101a0595adaae16c6ea9755583a4fab2d226271","creation_time":null}],"type":"user","memberroles":"","industry":null,"groups":""},"related_id":"7101a0595adaae16c6ea9755583a4fab2d226271","related_model":"Addressbook_Model_Contact","type":"","related_degree":"sibling"}],"attachments":[],"notes":[]},"format":"","definitionId":"8e987616ca07bc3dc51e05ad872434f891e474eb"}', true);

        $recordData = $jsonFEData['recordData'];
        $contact = new Addressbook_Model_Contact($recordData, true);

        $export = new Addressbook_Export_Doc(new Addressbook_Model_ContactFilter(), null,
            [
                'definitionId'  => $definition->getId(),
                'recordData'    => $recordData
            ]);
        $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(Tinebase_Record_Abstract::generateUID()));

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);

        static::assertContains($export->getTranslate()->_('Mr'), $plain);
        static::assertContains($contact->n_fn, $plain);
        static::assertContains($contact->n_given, $plain);
        $relatedRecord = new Addressbook_Model_Contact($contact->relations[0]['related_record'], true);
        static::assertContains($relatedRecord->getTitle(), $plain);
    }

    /**
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testExportLetter()
    {
        // privat
        $contactPrivat = new Addressbook_Model_Contact([
                'n_given' => 'Privat',
                'n_family' => 'Test Preferred',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contactPrivat Addressbook_Model_Contact */
        $contactPrivat = Addressbook_Controller_Contact::getInstance()->create($contactPrivat);

        // business
        $contactBusiness = new Addressbook_Model_Contact([
                'n_given' => 'Business',
                'n_family' => 'Test Preferred',
                'adr_one_street' => 'Business Street 22',
                'adr_one_postalcode' => '1235',
                'adr_one_locality' => 'Business City',
                'preferred_address' => 0
            ]
        );
        /* @var $contactBusiness Addressbook_Model_Contact */
        $contactBusiness = Addressbook_Controller_Contact::getInstance()->create($contactBusiness);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_family', 'operator' => 'equals', 'value' => $contactPrivat->n_family]
        ]);
        $export = new Addressbook_Export_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => 'Addressbook_Model_Contact',
                    'name' => 'adb_letter_doc'
                ]))->getFirstRecord()->getId()
            ]);

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);
     
        static::assertContains($contactPrivat->n_given, $plain);
        static::assertContains($contactPrivat->adr_two_street, $plain);
        static::assertContains($contactBusiness->n_given, $plain);
        static::assertContains($contactBusiness->adr_one_street, $plain);
    }

    /**
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testExportDetailDoc()
    {
        $contact = new Addressbook_Model_Contact([
                'n_given' => 'Privat',
                'n_family' => 'Test Contact with a relation',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contact Addressbook_Model_Contact */
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);

        $contactRelated = new Addressbook_Model_Contact([
                'n_given' => 'Privat Related',
                'n_family' => 'Test Related',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contactRelated Addressbook_Model_Contact */
        $contactRelated = Addressbook_Controller_Contact::getInstance()->create($contactRelated);

        Tinebase_Relations::getInstance()->setRelations(Addressbook_Model_Contact::class, 'Sql', $contact->getId(), [[
            'related_degree' => 'sibling',
            'related_model' => Addressbook_Model_Contact::class,
            'related_backend' => 'Sql',
            'related_id' => $contactRelated->getId(),
            'type' => 'Contact'
        ]]);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_family', 'operator' => 'equals', 'value' => $contact->n_family]
        ]);
        
        $export = new Addressbook_Export_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => Addressbook_Model_Contact::class,
                    'format' => 'docx',
                    'label' => 'Word details'
                ]))->getFirstRecord()->getId()
            ]);

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);

        static::assertContains($contactRelated->getTitle(), $plain);
    }

    public function testFactoryNoFilter()
    {
        $export = Tinebase_Export::factory(null, ['definitionId' => Tinebase_ImportExportDefinition::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                'model' => Addressbook_Model_Contact::class,
                'format' => 'docx',
                'label' => 'Word details'
            ]))->getFirstRecord()->getId()]);
        
        static::assertInstanceOf(Addressbook_Export_Doc::class, $export);
        static::assertSame($export->getFilter()->hash(), (new Addressbook_Model_ContactFilter())->hash());
        $export->registerTwigExtension(
            new Tinebase_Export_TwigExtensionCacheBust(Tinebase_Record_Abstract::generateUID()));

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);
        static::assertContains('James McBlack', $plain);
    }
}
