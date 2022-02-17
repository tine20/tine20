<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Sales_Export_Document
 */
class Sales_Document_ExportTest extends Sales_Document_Abstract
{
    protected function createExportData()
    {
        $boilerplate1 = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate([
                Sales_Model_Boilerplate::FLD_NAME => 'pretext'
            ]));
        $boilerplate2 = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate([
                Sales_Model_Boilerplate::FLD_NAME => 'posttext'
            ]));
        $customer = $this->_createCustomer();
        $customer->postal->{Sales_Model_Address::FLD_POSTALCODE} = '99999';
        Sales_Controller_Customer::getInstance()->update($customer);

        $customerData = $customer->toArray();
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_DOCUMENT_CATEGORY => Sales_Config::DOCUMENT_CATEGORY_DEFAULT,
            Sales_Model_Document_Offer::FLD_DOCUMENT_LANGUAGE => 'de',
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customerData['postal'],
            Sales_Model_Document_Offer::FLD_BOILERPLATES => [
                $boilerplate1->toArray(),
                $boilerplate2->toArray(),
            ],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
            Sales_Model_Document_Offer::FLD_INVOICE_DISCOUNT_SUM => 1,
            Sales_Model_Document_Offer::FLD_INVOICE_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
            Sales_Model_Document_Offer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'title',
                    Sales_Model_DocumentPosition_Offer::FLD_DESCRIPTION => 'desc',
                    Sales_Model_DocumentPosition_Offer::FLD_SORTING => 1,
                ], [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'title 1',
                    Sales_Model_DocumentPosition_Offer::FLD_DESCRIPTION => 'desc 1',
                    Sales_Model_DocumentPosition_Offer::FLD_SORTING => 2,
                    Sales_Model_DocumentPosition_Offer::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                    Sales_Model_DocumentPosition_Offer::FLD_POSITION_DISCOUNT_SUM => 1,
                ]
            ],
            Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE => [
                [ 'tax_rate' => 7, 'tax_sum' => 9.88 ], [ 'tax_rate' => 19, 'tax_sum' => 7.6788 ],
            ],
        ]);
        return (new Sales_Frontend_Json())->saveDocument_Offer($document->toArray(true));
    }

    public function testExportSimpleDocumentDocxOverwrite()
    {
        $document = $this->createExportData();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Offer::class, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $document['id']]
        ]);
        $doc = new Sales_Export_Document($filter, null, ['definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName('document_offer_docx')->getId()]);
        $pathProp = new ReflectionProperty($doc, '_templateFileName');
        $pathProp->setAccessible(true);
        $pathProp->setValue($doc, __DIR__ . '/files/overwrite/test.docx');
        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';

        try {
            $doc->generate();
            $doc->save($tempfile);
            $data = file_get_contents('zip://' . $tempfile . '#word/document.xml');
            static::assertStringContainsString('root/STANDARD/de/test.docx', $data);

            rename(__DIR__ . '/files/overwrite/STANDARD/de/test.docx', __DIR__ . '/files/overwrite/STANDARD/de/test.docx1');
            $pathProp->setValue($doc, __DIR__ . '/files/overwrite/test.docx');
            $doc->generate();
            $doc->save($tempfile);
            $data = file_get_contents('zip://' . $tempfile . '#word/document.xml');
            static::assertStringContainsString('root/STANDARD/test.docx', $data);

            rename(__DIR__ . '/files/overwrite/STANDARD/test.docx', __DIR__ . '/files/overwrite/STANDARD/test.docx1');
            $pathProp->setValue($doc, __DIR__ . '/files/overwrite/test.docx');
            $doc->generate();
            $doc->save($tempfile);
            $data = file_get_contents('zip://' . $tempfile . '#word/document.xml');
            static::assertStringContainsString('root/test.docx', $data);

        } finally {
            unlink($tempfile);
            @rename(__DIR__ . '/files/overwrite/STANDARD/de/test.docx1', __DIR__ . '/files/overwrite/STANDARD/de/test.docx');
            @rename(__DIR__ . '/files/overwrite/STANDARD/test.docx1', __DIR__ . '/files/overwrite/STANDARD/test.docx');
        }
    }

    public function testExportSimpleDocumentDocx()
    {
        $document = $this->createExportData();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Offer::class, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $document['id']]
        ]);
        $doc = new Sales_Export_Document($filter, null, ['definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName('document_offer_docx')->getId()]);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }

    public function testExportSimpleDocumentPdf()
    {
        $this->markTestSkipped('needs OOI, also, it doesnt clean up, as it needs to commit for OOI to work...');
        $this->_testNeedsTransaction();

        $document = $this->createExportData();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Offer::class, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $document['id']]
        ]);
        $doc = new Sales_Export_Document($filter, null, ['definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName('document_offer_pdf')->getId()]);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.pdf';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }
}
