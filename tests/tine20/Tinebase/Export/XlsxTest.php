<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Firebase\JWT\JWT;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Http\Message\RequestInterface;

/**
 * Test class for Tinebase_Export_Doc
 *
 * @todo: add some more real assertion, filesize comparison doesn't fit us
 * 
 * @package     Tinebase
 */
class Tinebase_Export_XlsxTest extends TestCase
{
    protected function _assertTemplateVersionHandling($template, $expectedString)
    {
        /** @var Addressbook_Export_Xls $export */
        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'xls',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_xls.xml',
                'template'           => $template,
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());
        $export->generate();
        $tmpFile = Tinebase_TempFile::getTempPath();
        $export->write($tmpFile);
        $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(uniqid()));

        try {
            $reader = IOFactory::createReader('Xlsx');
            $doc = $reader->load($tmpFile);

            $arrayData = $doc->getActiveSheet()->rangeToArray('A1:A2');
            static::assertEquals($expectedString, $arrayData[0][0]);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testExportTemplateVersionHandlingNoConstraintGiven()
    {
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling1.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.1.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling2.xlsx'));

        $this->_assertTemplateVersionHandling(
            'tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit.xlsx', 'handling2');
    }

    public function testExportTemplateVersionHandlingWithConstraints1()
    {
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling1.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.1.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling2.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v2.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling3.xlsx'));

        $this->_assertTemplateVersionHandling(
            'tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v^1.xlsx', 'handling2');
    }

    public function testExportTemplateVersionHandlingWithConstraints2()
    {
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling1.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.1.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling2.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v2.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling3.xlsx'));

        $this->_assertTemplateVersionHandling(
            'tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v^1.0.xlsx', 'handling2');
    }

    public function testExportTemplateVersionHandlingWithConstraints3()
    {
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling1.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.1.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling2.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v2.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling3.xlsx'));

        $this->_assertTemplateVersionHandling(
            'tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v~1.0.0.xlsx', 'handling1');
    }

    public function testExportTemplateVersionHandlingWithConstraints4()
    {
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.0.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling1.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.1.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling2.xlsx'));
        file_put_contents('tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v1.2.xlsx',
            file_get_contents(dirname(__DIR__) . '/files/export/versionHandling3.xlsx'));

        $this->_assertTemplateVersionHandling(
            'tine20:///Tinebase/folders/shared/export/templates/Addressbook/unit-v ~1.0 | ~3.0 .xlsx', 'handling3');
    }

    public function testXlsxTwigFunctions()
    {
        /** @var Addressbook_Export_Xls $export */
        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'xls',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_xls.xml',
                'template'           => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions.xlsx',
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());

        $export->generate();
        $tmpFile = Tinebase_TempFile::getTempPath();
        $export->write($tmpFile);


        $reader = IOFactory::createReader('Xlsx');
        $doc = $reader->load($tmpFile);
        
        // CZ is enough for contact, but to allow growth DZ is on the safe side
        $arrayData = $doc->getActiveSheet()->rangeToArray('A3:DZ3');
        
        // Testing twig date format
        static::assertEquals('Jan 2, 2000', $arrayData[0][0]);
        
        // @todo test all other twig functions here! :-)
    }

    /**
     * Tested cf types:
     *  - record
     *  - recordList
     *
     * tests relations
     *
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     */
    public function testAddressbookCustomFieldRelations()
    {
        $skipWithApp = [
            'FinanzPlusIntegrator',
            'MemberManagement',
            'WebUntis',
        ];
        foreach ($skipWithApp as $app) {
            if (Tinebase_Application::getInstance()->isInstalled($app)) {
                self::markTestSkipped('does not work with app ' . $app);
            }
        }

        $cfController = Tinebase_CustomField::getInstance();
        $contactController = Addressbook_Controller_Contact::getInstance();

        $scleverContact = $contactController->get($this->_personas['sclever']->contact_id);
        $jmcblackContact = $contactController->get($this->_personas['jmcblack']->contact_id);

        $recordCF = $cfController->addCustomField(new Tinebase_Model_CustomField_Config([
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => 'contactCF',
            'model'             => Addressbook_Model_Contact::class,
            'definition'        => [
                'label'             => 'contact',
                'type'              => 'record',
                'recordConfig'      => ['value' => ['records' => 'Tine.Addressbook.Model.Contact']]
            ]
        ]));
        $recordListCF = $cfController->addCustomField(new Tinebase_Model_CustomField_Config([
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => 'contactListCF',
            'model'             => Addressbook_Model_Contact::class,
            'definition'        => [
                'label'             => 'contacts',
                'type'              => 'recordList',
                'recordListConfig'  => ['value' => ['records' => 'Tine.Addressbook.Model.Contact']]
            ]
        ]));

        $testContact = new Addressbook_Model_Contact([
            'customfields'  => [
                $recordCF->name => $scleverContact,
                $recordListCF->name => [$scleverContact, $jmcblackContact]
            ],
            'relations'     => [
                [
                    'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_id'        => $scleverContact->getId(),
                    'related_model'     => Addressbook_Model_Contact::class,
                    'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'type'              => 'type1'
                ], [
                    'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
                    'related_id'        => $jmcblackContact->getId(),
                    'related_model'     => Addressbook_Model_Contact::class,
                    'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'type'              => 'type2'
                ]
            ]
        ]);

        $testContact->n_given = 'Test Contact Name 123';
        $testContact->n_family = 'Test Name';

        $testContact = Addressbook_Controller_Contact::getInstance()->create($testContact);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $testContact->n_given]
        ]);
        $export = new Addressbook_Export_Xls($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => 'Addressbook_Model_Contact',
                    'name' => 'adb_xls'
                ]))->getFirstRecord()->getId()
            ]);

        $xls = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->write($xls);


        $reader = IOFactory::createReader('Xlsx');
        $doc = $reader->load($xls);
        // CZ is enough for contact, but to allow growth DZ is on the safe side
        $arrayData = $doc->getActiveSheet()->rangeToArray('A3:DZ4');
        $printRdata0 = print_r($arrayData[0], true);

        // check some indexes!
        $nameIndex = array_search('Last Name', $arrayData[0], true);
        static::assertEquals($testContact->n_family, $arrayData[1][$nameIndex]);
        
        // test resolving of user fields
        $createdByIndex = array_search('Created By', $arrayData[0], true);
        static::assertEquals(Tinebase_Core::getUser()->accountDisplayName, $arrayData[1][$createdByIndex]);
        
        foreach ($testContact->getFields() as $field) {
            if ('customfields' === $field) {
                static::assertFalse(in_array($field, $arrayData[0]), 'mustn\'t find customfields in ' . $printRdata0);
            }
        }

        $recordCFfield = $export->getTranslate()->_($recordCF->definition->label);
        static::assertTrue(false !== ($recordCFKey = array_search($recordCFfield, $arrayData[0])),
            'couldn\'t find field ' . $recordCFfield . ' in ' . $printRdata0);
        
        static::assertEquals($scleverContact->getTitle(), $arrayData[1][$recordCFKey],
            $recordCFfield . ' not as expected: ' . print_r($arrayData[1], true) . PHP_EOL . $recordCFKey);

        $recordListCFfield = $export->getTranslate()->_($recordListCF->definition->label);
        static::assertTrue(false !== ($recordListCFKey = array_search($recordListCFfield, $arrayData[0])),
            'couldn\'t find field ' . $recordListCFfield . ' in ' . $printRdata0);
        static::assertEquals($jmcblackContact->getTitle() . ', ' . $scleverContact->getTitle(),
            $arrayData[1][$recordListCFKey], $recordListCFfield . ' not as expected: ' . print_r($arrayData[1], true));
        
        $systemFieldCount = 0;
        foreach(Addressbook_Model_Contact::getConfiguration()->getFields() as $field) {
            if (isset($field['system']) && $field['system'] === true) {
                $systemFieldCount++;
            }
        }

        $cfConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook',
            Addressbook_Model_Contact::class);
        foreach ($cfConfigs as $cfConfig) {
            $field = $export->getTranslate()->_(empty($cfConfig->definition->label) ? $cfConfig->name :
                $cfConfig->definition->label);
            static::assertTrue(in_array($field, $arrayData[0]), 'couldn\'t find field ' . $field . ' in '
                . $printRdata0);
        }

        // TODO fix MO adapter
        if (Tinebase_Translation::getTranslation('Tinebase') instanceof Zend_Translate_Adapter_GettextPo) {
            // test the relations
            $relationsField = $export->getTranslate()->_('Relations');
            static::assertTrue(false !== ($relationsKey = array_search($relationsField, $arrayData[0])),
                'couldn\'t find field ' . $relationsField . ' in ' . $printRdata0);

            $modelTranslated = $export->getTranslate()->_('Contact');
            static::assertEquals($modelTranslated . ' type2 ' . $jmcblackContact->getTitle() . ', ' . $modelTranslated .
                ' type1 ' . $scleverContact->getTitle(),
                $arrayData[1][$relationsKey], $relationsField . ' not as expected: ' . print_r($arrayData[1], true));
        }
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testTranslatedHeadline()
    {
        Tinebase_Core::setupUserLocale('de');

        $testContact = new Addressbook_Model_Contact([]);

        $testContact->n_given = 'Test Contact Name 123';
        $testContact->n_family = 'Test Name';

        $testContact = Addressbook_Controller_Contact::getInstance()->create($testContact);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => $testContact->n_given]
        ]);
        $export = new Addressbook_Export_Xls($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => 'Addressbook_Model_Contact',
                    'name' => 'adb_xls'
                ]))->getFirstRecord()->getId()
            ]);

        $xls = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->write($xls);

        $reader = IOFactory::createReader('Xlsx');
        $doc = $reader->load($xls);
        // CZ is enough for contact, but to allow growth DZ is on the safe side
        $arrayData = $doc->getActiveSheet()->rangeToArray('A3:DZ4');
        $flippedArrayData = array_flip(array_filter($arrayData[0]));

        $msg = print_r($flippedArrayData, true);
        static::assertArrayHasKey('Verknüpfungen', $flippedArrayData, $msg);
        static::assertArrayHasKey('Tags', $flippedArrayData, $msg);
        static::assertArrayHasKey('Telefon', $flippedArrayData, $msg);
        static::assertArrayHasKey('Raum', $flippedArrayData, $msg);
        static::assertArrayHasKey('Vorname', $flippedArrayData, $msg);
    }

    public function testConvertToPdf()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} != true
            || Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERSION}  < 2
        ) {
            $this->markTestSkipped('no docservice configured');
        }

        /** @var Addressbook_Export_Xls $export */
        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'xls',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_xls.xml',
                'template'           => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions.xlsx',
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());

        $export->generate();

        $file = null;
        try {
            $file = $export->convert(Tinebase_Export_Convertible::PDF);
            $this->assertEquals('application/pdf', mime_content_type($file));

        } finally {
            if ($file) {
                unlink($file);
            }
        }
    }

    public function testExpressiveApiWithBrokenToken()
    {
        $definitionId = Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
            'model' => 'Addressbook_Model_Contact',
            'name' => 'adb_xls'
        ]))->getFirstRecord()->getId();

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'POST /Tinebase/export/' . $definitionId . ' HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'Authorization: Bearer lalalala' . "\r\n"
            . "\r\n"
            . json_encode(['filter' => [
                ['field' => 'n_given', 'operator' => 'equals', 'value' => 'shalala']
            ]]) . "\r\n\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);
        Tinebase_Core::unsetUser();
        unset(Tinebase_Session::getSessionNamespace()->currentAccount);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);
        $server->handle();

        $this->assertSame(401, $emitter->response->getStatusCode());
    }

    public function testExpressiveApiWithToken()
    {
        $definitionId = Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
            'model' => 'Addressbook_Model_Contact',
            'name' => 'adb_xls'
        ]))->getFirstRecord()->getId();

        $testContact = new Addressbook_Model_Contact([]);
        $testContact->n_given = 'Test Contact Name 123';
        $testContact->n_family = 'Test Name';
        $testContact = Addressbook_Controller_Contact::getInstance()->create($testContact);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        /*$jwtRoutes = */Admin_Controller_JWTAccessRoutes::getInstance()->create(new Admin_Model_JWTAccessRoutes([
            Admin_Model_JWTAccessRoutes::FLD_ACCOUNTID => $this->_originalTestUser->getId(),
            Admin_Model_JWTAccessRoutes::FLD_ROUTES => [Tinebase_Export_Abstract::class . '::expressiveApi'],
            Admin_Model_JWTAccessRoutes::FLD_ISSUER => 'unittest',
            Admin_Model_JWTAccessRoutes::FLD_KEY => 'unittest',
        ]));
        $token = JWT::encode([
            'iss' => 'unittest'
        ], 'unittest', 'HS256');

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'POST /Tinebase/export/' . $definitionId . ' HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'Authorization: Bearer ' . $token . "\r\n"
            . "\r\n"
            . json_encode(['filter' => [
                ['field' => 'n_given', 'operator' => 'equals', 'value' => $testContact->n_given]
            ]]) . "\r\n\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);
        Tinebase_Core::unsetUser();
        unset(Tinebase_Session::getSessionNamespace()->currentAccount);

        $server->handle();

        $this->assertSame(200, $emitter->response->getStatusCode());

        $tmpFile = Tinebase_TempFile::getTempPath();
        $raii = new Tinebase_RAII(function() use($tmpFile) {
            unlink($tmpFile);
        });
        file_put_contents($tmpFile, (string)$emitter->response->getBody());

        $reader = IOFactory::createReader('Xlsx');
        $doc = $reader->load($tmpFile);
        // CZ is enough for contact, but to allow growth DZ is on the safe side
        $arrayData = $doc->getActiveSheet()->rangeToArray('A3:DZ4');
        $flippedArrayData = array_flip(array_filter($arrayData[1]));

        $msg = print_r($flippedArrayData, true);
        $this->assertArrayHasKey($testContact->n_given, $flippedArrayData, $msg);
        $this->assertArrayHasKey($testContact->n_family, $flippedArrayData, $msg);

        unset($raii);
    }

    public function testExpressiveApi()
    {
        $definitionId = Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
            'model' => 'Addressbook_Model_Contact',
            'name' => 'adb_xls'
        ]))->getFirstRecord()->getId();

        $testContact = new Addressbook_Model_Contact([]);
        $testContact->n_given = 'Test Contact Name 123';
        $testContact->n_family = 'Test Name';
        $testContact = Addressbook_Controller_Contact::getInstance()->create($testContact);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'POST /Tinebase/export/' . $definitionId . ' HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . "\r\n"
            . json_encode(['filter' => [
                ['field' => 'n_given', 'operator' => 'equals', 'value' => $testContact->n_given]
            ]]) . "\r\n\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        $tmpFile = Tinebase_TempFile::getTempPath();
        $raii = new Tinebase_RAII(function() use($tmpFile) {
            unlink($tmpFile);
        });
        file_put_contents($tmpFile, (string)$emitter->response->getBody());

        $reader = IOFactory::createReader('Xlsx');
        $doc = $reader->load($tmpFile);
        // CZ is enough for contact, but to allow growth DZ is on the safe side
        $arrayData = $doc->getActiveSheet()->rangeToArray('A3:DZ4');
        $flippedArrayData = array_flip(array_filter($arrayData[1]));

        $msg = print_r($flippedArrayData, true);
        $this->assertArrayHasKey($testContact->n_given, $flippedArrayData, $msg);
        $this->assertArrayHasKey($testContact->n_family, $flippedArrayData, $msg);

        unset($raii);
    }
}
