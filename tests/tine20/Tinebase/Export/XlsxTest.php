<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Export_Doc
 *
 * @package     Tinebase
 */
class Tinebase_Export_XlsxTest extends TestCase
{

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

        try {
            static::assertEquals(filesize(dirname(__DIR__) . '/files/export/twigFunctions_result.xlsx'),
                filesize($tmpFile));
        } finally {
            unlink($tmpFile);
        }
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
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(new Tinebase_Model_ImportExportDefinitionFilter([
                    'model' => 'Addressbook_Model_Contact',
                    'name' => 'adb_xls'
                ]))->getFirstRecord()->getId()
            ]);

        $xls = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->write($xls);


        $reader = PHPExcel_IOFactory::createReader('Excel2007');
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

        $cfConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook',
            Addressbook_Model_Contact::class);
        foreach ($cfConfigs as $cfConfig) {
            $field = $export->getTranslate()->_(empty($cfConfig->definition->label) ? $cfConfig->name :
                $cfConfig->definition->label);
            static::assertTrue(in_array($field, $arrayData[0]), 'couldn\'t find field ' . $field . ' in '
                . $printRdata0);
        }

        $recordCFfield = $export->getTranslate()->_($recordCF->definition->label);
        static::assertTrue(false !== ($recordCFKey = array_search($recordCFfield, $arrayData[0])),
            'couldn\'t find field ' . $recordCFfield . ' in ' . $printRdata0);
        
        static::assertEquals($scleverContact->getTitle(), $arrayData[1][$recordCFKey],
            $recordCFfield . ' not as expected: ' . print_r($arrayData[1], true));

        $recordListCFfield = $export->getTranslate()->_($recordListCF->definition->label);
        static::assertTrue(false !== ($recordListCFKey = array_search($recordListCFfield, $arrayData[0])),
            'couldn\'t find field ' . $recordListCFfield . ' in ' . $printRdata0);
        static::assertEquals($scleverContact->getTitle() . ', ' . $jmcblackContact->getTitle(),
            $arrayData[1][$recordListCFKey], $recordListCFfield . ' not as expected: ' . print_r($arrayData[1], true));
        
        $systemFieldCount = 0;
        foreach(Addressbook_Model_Contact::getConfiguration()->getFields() as $field) {
            if (isset($field['system']) && $field['system'] === true) {
                $systemFieldCount++;
            }
        }
        
        $filteredHeadLine = array_filter($arrayData[0]);
        static::assertEquals(count($testContact->getFields()) - 4 - $systemFieldCount + $cfConfigs->count(), count($filteredHeadLine),
            'count of fields + customfields - "customfields property" does not equal amount of headline columns');
        
        // test the relations
        $relationsField = $export->getTranslate()->_('relations');
        static::assertTrue(false !== ($relationsKey = array_search($relationsField, $arrayData[0])),
            'couldn\'t find field ' . $relationsField . ' in ' . $printRdata0);

        $modelTranslated = $export->getTranslate()->_('Contact');
        static::assertEquals($modelTranslated . ' type1 ' . $scleverContact->getTitle() . ', ' . $modelTranslated .
            ' type2 ' . $jmcblackContact->getTitle(),
            $arrayData[1][$relationsKey], $relationsField . ' not as expected: ' . print_r($arrayData[1], true));
    }
}