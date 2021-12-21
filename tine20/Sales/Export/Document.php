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
    //use Tinebase_Export_DocumentPdfTrait;

    protected function _onBeforeExportRecords()
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

        $vats = new Tinebase_Record_RecordSet(Tinebase_Config_KeyFieldRecord::class, []);
        foreach ($this->_records->getFirstRecord()->{Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE} as $vat) {
            $vats->addRecord(new Tinebase_Config_KeyFieldRecord([
                'id' => $vat['tax_rate'],
                'value' => $vat['tax_sum'],
            ]));
        }
        new Tinebase_Config_KeyFieldRecord();
        $this->_records = [
            'PREPOSITIONS' => $this->_records,
            'POSITIONS' => $this->_records->getFirstRecord()->{Sales_Model_Document_Abstract::FLD_POSITIONS},
            'POSTPOSITIONS' => $this->_records,
            'VATS' => $vats,
            'POSTVATS' => $this->_records,
        ];

        parent::_onBeforeExportRecords();
    }

   /* protected function _getOldFormat()
    {
        return 'docx';
    }

    /**
     * output result
     *
     * @param string $_target
     * @return string result
     *
    public function write($_target = null)
    {
        $this->_parentFile = null;
        try {
            return parent::write($_target);
        } finally {
            if (null !== $this->_parentFile) {
                @unlink($this->_parentFile);
                $this->_parentFile = null;
            }
        }
    }*/
}
