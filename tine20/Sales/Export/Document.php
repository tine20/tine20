<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Sales_Export_Document
 */
class Sales_Export_Document extends Tinebase_Export_DocV2
{
    // we need to set locale etc before loading twig, so we overwrite _loadTwig
    protected function _loadTwig()
    {
        $this->_records = $this->_controller->search($this->_filter);
        if ($this->_records->count() !== 1) {
            throw new Tinebase_Exception_Record_Validation('can only export exactly one document at a time');
        }

        (new Tinebase_Record_Expander($this->_modelName, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'delivery'      => [],
                        'billing'       => [],
                        'postal'        => [],
                        'cpextern_id'   => [],
                        'cpintern_id'   => [],
                    ],
                ],
                Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => [],
                Sales_Model_Document_Abstract::FLD_POSITIONS => [],
                Sales_Model_Document_Abstract::FLD_BOILERPLATES => [],
            ]
        ]))->expand($this->_records);

        /** @var Sales_Model_Document_Abstract $record */
        $record = $this->_records->getFirstRecord();
        $cat = $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY};
        $lang = $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE};
        $this->_locale = new Zend_Locale($lang);
        Sales_Model_DocumentPosition_Abstract::setExportContextLocale($this->_locale);
        $this->_translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $this->_locale);

        if (null !== ($overwriteTemplate = $this->_findOverwriteTemplate($this->_templateFileName, [
                    $lang => null,
                    $cat => [
                        $lang => null,
                    ],
                ]))) {
            $this->_templateFileName = $overwriteTemplate;
            $this->_createDocument();
        }

        // do this after any _createDocument calls! otherwise we lose the watermark
        if (!$record->isBooked()) {
            $this->_docTemplate->addWaterMark('PROFORMA', 1);
            $this->_docTemplate->addWaterMark('PROFORMA', 2);
        }

        $vats = new Tinebase_Record_RecordSet(Tinebase_Config_KeyFieldRecord::class, []);
        foreach ($record->{Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE} as $vat) {
            $vats->addRecord(new Tinebase_Config_KeyFieldRecord([
                'id' => $vat['tax_rate'],
                'value' => $vat['tax_sum'],
            ], true));
        }
        $this->_records = [
            'PREPOSITIONS' => $this->_records,
            'POSITIONS' => $record->{Sales_Model_Document_Abstract::FLD_POSITIONS},
            'POSTPOSITIONS' => $this->_records,
            'VATS' => $vats,
            'POSTVATS' => $this->_records,
        ];

        parent::_loadTwig();
    }

    /**
     * @param Tinebase_Record_Interface|null $_record
     */
    protected function _renderTwigTemplate($_record = null)
    {
        if (null === $_record) {
            $_record = $this->_records['PREPOSITIONS']->getFirstRecord();
        }
        parent::_renderTwigTemplate($_record);
    }
}
