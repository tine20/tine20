<?php declare(strict_types=1);

/**
 * Abstract Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Abstract Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
abstract class Sales_Controller_Document_Abstract extends Tinebase_Controller_Record_Abstract
{
    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        }
        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
            if ($address = Sales_Controller_Document_Address::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Address::class, [
                        ['field' => 'id', 'operator' => 'equals', 'value' => $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->getId()],
                        ['field' => 'document_id', 'operator' => 'equals', 'value' => null],
                        ['field' => 'customer_id', 'operator' => 'not', 'value' => null],
                    ]))->getFirstRecord()) {
                $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->setId($address->{Sales_Model_Address::FLD_ORIGINAL_ID});
                $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_ORIGINAL_ID} = null;
            }
        }

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    public static function createPrecursorTree(string $documentModel, array $documentIds, array &$resolvedIds, array $expanderDef): Tinebase_Record_RecordSet
    {
        $result = new Tinebase_Record_RecordSet(Tinebase_Model_DynamicRecordWrapper::class, []);
        $documentIds = array_diff($documentIds, $resolvedIds);
        if (empty($documentIds)) {
            return $result;
        }
        $resolvedIds = array_merge($documentIds, $resolvedIds);

        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance($documentModel);
        $todos = [];
        /** @var Sales_Model_Document_Abstract $document */
        foreach ($ctrl->getMultiple($documentIds, false, new Tinebase_Record_Expander($documentModel, $expanderDef)) as
                $document) {
            /** @var Tinebase_Model_DynamicRecordWrapper $wrapper */
            foreach ($document->xprops(Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS) as $wrapper) {
                if (in_array($wrapper->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD}, $resolvedIds)) continue;
                if (!isset($todos[$wrapper->{Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME}])) {
                    $todos[$wrapper->{Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME}] = [];
                }
                $todos[$wrapper->{Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME}][] = $wrapper->record;
            }
            $result->addRecord(new Tinebase_Model_DynamicRecordWrapper([
                Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => $documentModel,
                Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $document,
            ]));
        }

        $filterArray = [
            'condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
            'filters' => [],
        ];
        foreach ($documentIds as $documentId) {
            $filterArray['filters'][] =
                ['field' => Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS, 'operator' => 'contains',
                    'value' => '"' . $documentId . '"'];
        }
        foreach (static::getDocumentModels() as $docModel) {
            /** @var Tinebase_Controller_Record_Abstract $ctrl */
            $ctrl = Tinebase_Core::getApplicationInstance($docModel);
            $ids = $ctrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($docModel, $filterArray), null, false, true);
            $ids = array_diff($ids, $resolvedIds);
            if (!empty($ids)) {
                if (!isset($todos[$docModel])) {
                    $todos[$docModel] = [];
                }
                $todos[$docModel] = array_merge($todos[$docModel], $ids);
            }
        }

        foreach ($todos as $docModel => $ids) {
            $result->merge(static::createPrecursorTree($docModel, array_unique($ids), $resolvedIds, $expanderDef));
        }

        return $result;
    }

    /**
     * @return array<string>
     */
    public static function getDocumentModels(): array
    {
        return [
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ];
    }
}
