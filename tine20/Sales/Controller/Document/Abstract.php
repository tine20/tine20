<?php declare(strict_types=1);

/**
 * Abstract Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_documentStatusConfig = null;
    protected $_documentStatusTransitionConfig = null;
    protected $_documentStatusField = '';
    protected $_oldRecordBookWriteableFields = null;
    protected $_bookRecordRequiredFields = null;

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        // the recipient address is not part of a customer, we enforce that here
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        }

        if ($this->_documentStatusConfig && $this->_documentStatusField) {
            if ($this->_documentStatusTransitionConfig) {
                $this->_validateTransitionState($this->_documentStatusField,
                    Sales_Config::getInstance()->{$this->_documentStatusTransitionConfig}, $_record);
            }

            if (Sales_Config::getInstance()->{$this->_documentStatusConfig}->records
                    ->getById($_record->{$this->_documentStatusField})
                    ->{Sales_Model_Document_Status::FLD_BOOKED}) {
                $this->_inspectBeforeForBookedRecord($_record);
            }
        }

        $_record->calculatePricesIncludingPositions();

        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @param Sales_Model_Document_Abstract $_record
     * @param Sales_Model_Document_Abstract $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            // the recipient address is not part of a customer, we enforce that here
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;

            // if the recipient address is a denormalized customer address, we denormalize it again from the original address
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

        if ($this->_documentStatusConfig && $this->_documentStatusField) {
            if ($this->_documentStatusTransitionConfig) {
                $this->_validateTransitionState($this->_documentStatusField,
                    Sales_Config::getInstance()->{$this->_documentStatusTransitionConfig}, $_record, $_oldRecord);
            }

            if (Sales_Config::getInstance()->{$this->_documentStatusConfig}->records
                    ->getById($_oldRecord->{$this->_documentStatusField})
                    ->{Sales_Model_Document_Status::FLD_BOOKED}) {
                $this->_inspectBeforeForBookedOldRecord($_record, $_oldRecord);
            }

            if (Sales_Config::getInstance()->{$this->_documentStatusConfig}->records
                    ->getById($_record->{$this->_documentStatusField})
                    ->{Sales_Model_Document_Status::FLD_BOOKED}) {
                $this->_inspectBeforeForBookedRecord($_record, $_oldRecord);
            }
        }

        // lets check if positions got removed and if that would affect our precursor documents
        if (null !== $_record->{Sales_Model_Document_Abstract::FLD_POSITIONS}) {
            if (!$_record->{Sales_Model_Document_Abstract::FLD_POSITIONS} instanceof Tinebase_Record_RecordSet) {
                throw new Tinebase_Exception_Record_Validation(Sales_Model_Document_Abstract::FLD_POSITIONS .
                    ' needs to be instance of ' . Tinebase_Record_RecordSet::class);
            }
            Tinebase_Record_Expander::expandRecord($_oldRecord);
            $deletedPositions = [];
            foreach ($_oldRecord->{Sales_Model_Document_Abstract::FLD_POSITIONS} as $oldPosition) {
                if (!$_record->{Sales_Model_Document_Abstract::FLD_POSITIONS}->getById($oldPosition->getId())) {
                    $deletedPositions[$oldPosition->getId()] = $oldPosition;
                }
            }
            $checkedDocumentIds = [];
            $removeDocumentIds = [];
            /** @var Sales_Model_DocumentPosition_Abstract $delPos */
            foreach ($deletedPositions as $delPos) {
                if (!$delPos->{Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION}) {
                    continue;
                }
                $docId = $delPos->{Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION}
                    ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID};
                if (isset($checkedDocumentIds[$docId])) {
                    continue;
                }
                $checkedDocumentIds[$docId] = true;
                if (!$_oldRecord->{Sales_Model_Document_Abstract::FLD_POSITIONS}->find(
                        function(Sales_Model_DocumentPosition_Abstract $pos) use($deletedPositions, $docId) {
                            return $pos->{Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION}
                                && $docId === $pos->{Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION}
                                ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID}
                                && !isset($deletedPositions[$pos->getId()]);
                        }, null)) {
                    $removeDocumentIds[$docId] = $docId;
                }
            }

            if (!empty($removeDocumentIds)) {
                $_record->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS} = $_oldRecord
                    ->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS};

                /** @var Tinebase_Model_DynamicRecordWrapper $precursor */
                foreach ($_record->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS} as $precursor) {
                    if (isset($removeDocumentIds[$precursor->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD}])) {
                        $_record->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->removeRecord($precursor);
                    }
                }
            }
        }

        $_record->calculatePricesIncludingPositions();

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    protected function _inspectBeforeForBookedOldRecord(Sales_Model_Document_Abstract $_record, Sales_Model_Document_Abstract $_oldRecord)
    {
        if (is_array($this->_oldRecordBookWriteableFields)) {
            // when oldRecord is booked, enforce read only
            foreach ($_record->getConfiguration()->fields as $field => $fConf) {
                if (in_array($field, $this->_oldRecordBookWriteableFields)) {
                    continue;
                }
                $_record->{$field} = $_oldRecord->{$field};
            }
        }
    }

    protected function _inspectBeforeForBookedRecord(Sales_Model_Document_Abstract $_record, ?Sales_Model_Document_Abstract $_oldRecord = null)
    {
        // when booked and no document_date set, set it to now
        if (! $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE}) {
            $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE} = Tinebase_DateTime::now();
        }

        if ($_oldRecord) {
            Tinebase_Record_Expander::expandRecord($_oldRecord);
        }

        if (is_array($this->_bookRecordRequiredFields)) {
            foreach ($this->_bookRecordRequiredFields as $field) {
                if (!$_record->{$field} && (null === $_oldRecord || $_record->__isset($field) || !$_oldRecord->{$field})) {
                    throw new Tinebase_Exception_SystemGeneric($field . ' needs to be set for a booked document');
                }
            }
        }
    }

    protected function _inspectDelete(array $_ids)
    {
        if ($this->_documentStatusConfig && $this->_documentStatusField) {
            // do not deleted booked records
            foreach ($this->getMultiple($_ids) as $record) {
                if (Sales_Config::getInstance()->{$this->_documentStatusConfig}->records
                        ->getById($record->{$this->_documentStatusField})
                        ->{Sales_Model_Document_Status::FLD_BOOKED}) {
                    unset($_ids[array_search($record->getId(), $_ids)]);
                }
            }
        }
        return parent::_inspectDelete($_ids);
    }

    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} = Sales_Controller_Document_Customer::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Customer::class, [
                ['field' => Sales_Model_Document_Customer::FLD_DOCUMENT_ID, 'operator' => 'equals', 'value' => $_record->getId()],
            ]))->getFirstRecord();
        parent::_deleteRecord($_record);
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

        $filterArray = [[
            'condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
            'filters' => [],
        ]];
        foreach ($documentIds as $documentId) {
            $filterArray[0]['filters'][] =
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

    public static function executeTransition(Sales_Model_Document_Transition $transition): Sales_Model_Document_Abstract
    {
        $transactionRAII = Tinebase_RAII::getTransactionManagerRAII();


        // we need to make sure that we actually reload all transition data (documents, positions, etc.) from the db
        /** @var Sales_Model_Document_TransitionSource $sourceDocument */
        foreach ($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS} as $sourceDocument) {
            if ($sourceDocument->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT} instanceof Sales_Model_Document_Abstract) {
                $sourceDocument->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT} =
                    $sourceDocument->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}->getId();
            }
            if ($sourceDocument->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS}) {
                /** @var Sales_Model_DocumentPosition_TransitionSource $sourcePosition */
                foreach ($sourceDocument->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS} as $sourcePosition) {
                    if ($sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION} instanceof Sales_Model_DocumentPosition_Abstract) {
                        $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION} =
                            $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}->getId();
                    }
                }
            }
        }

        // then do expanding to fetch data from db
        (new Tinebase_Record_Expander(Sales_Model_Document_Transition::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => [],
                                Sales_Model_Document_Abstract::FLD_POSITIONS => [],
                            ],
                        ],
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION => []
                            ],
                        ],
                    ],
                ],
            ],
        ]))->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Transition::class, [
            $transition
        ]));

        // do the transition
        /** @var Sales_Model_Document_Abstract $targetDocument */
        $targetDocument = new $transition->{Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE}([], true);
        $targetDocument->transitionFrom($transition);

        // save result in db
        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance(get_class($targetDocument));
        /** @var Sales_Model_Document_Abstract $result */
        $result = $ctrl->create($targetDocument);

        $transactionRAII->release();

        return $result;
    }
}
