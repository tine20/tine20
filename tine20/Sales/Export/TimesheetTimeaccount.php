<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Sales_Export_TimesheetTimeaccount
 */
class Sales_Export_TimesheetTimeaccount extends Tinebase_Export_Xls
{
    /**
     * Summarize records with a certain tag
     */
    const TAG_SUM = 'Bereitschaft';
    
    /**
     * @var Timetracker_Model_Timeaccount
     */
    protected $_timeaccount;

    /**
     * @var Sales_Model_Invoice
     */
    protected $_invoice;

    /**
     * @var bool
     */
    protected $_writeGenericHeader = false;

    /**
     * Sales_Export_TimesheetTimeaccount constructor.
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface|null $_controller
     * @param array $_additionalOptions
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter,
        Tinebase_Controller_Record_Interface $_controller = null,
        array $_additionalOptions = array()
    ) {
        if (isset($_additionalOptions['timeaccount'], $_additionalOptions['invoice'])) {
            $this->_timeaccount = $_additionalOptions['timeaccount'];
            $this->_invoice = $_additionalOptions['invoice'];
        } else {
            throw new InvalidArgumentException('Customized exporter requires Invoice and Timeaccount records to be passed.');
        }

        $this->_twigMapping = [];
        $this->_modelName = Timetracker_Model_Timesheet::class;
        $this->_applicationName = 'Timetracker';
        
        parent::__construct($_filter, Timetracker_Controller_Timesheet::getInstance(), $_additionalOptions);
    }


    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        /* @var $record Tinebase_Record_Abstract */
        if (empty($this->_records)) {
            return parent::_getTwigContext($context);
        }
        
        $customers = $this->_invoice->relations;
        $customer = $customers->filter('type', 'CUSTOMER')->getFirstRecord();
        $contract = $customers->filter('type', 'CONTRACT')->getFirstRecord();


        $tagSum = 0;
        $sum = 0;
        
        foreach($this->_records as $record) {
            if (!$record->tags) {
                continue;
            }
            
            if (($record->tags->filter('name', static::TAG_SUM))->count() > 0) {
                $tagSum += $record->duration;
            } else {
                $sum += $record->duration; 
            }
        }    

        return parent::_getTwigContext($context + [
                'invoice' => $this->_invoice,
                'contract' => $contract ? $contract->related_record->number : '',
                'customer' => $customer ? $customer->related_record->getTitle() : '',
                'timeaccount' => $this->_timeaccount,
                'sumTag' =>  $tagSum,
                'sum' => $sum
            ]
        );
    }

    /**
     *
     */
    protected function _exportRecords()
    {
        parent::_exportRecords();
    }

    /**
     *
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();

        // Maybe somewhere one day there could be something, we throw it out. We are going to export Timetracker_Model_Timesheets not invoices
        if ($this->_records) {
            $this->_records->removeAll();
        }

        $filter = new Timetracker_Model_TimesheetFilter([
            [
                'field' => 'timeaccount_id',
                'operator' => 'AND',
                'value' => [
                    [
                        'condition' => 'OR',
                        'filters' => [
                            ['field' => 'budget', 'operator' => 'equals', 'value' => 0],
                            ['field' => 'budget', 'operator' => 'equals', 'value' => null]
                        ]
                    ],
                    [
                        'field' => ':id',
                        'operator' => 'equals',
                        'value' => $this->_timeaccount->getId()
                    ],
                ]
            ]
        ]);
        $filter->addFilter(new Tinebase_Model_Filter_Text([
            'field' => 'invoice_id',
            'operator' => 'equals',
            'value' => $this->_invoice->getId()
        ]));
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);
        $this->_records = $timesheets;
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        // @todo we need a more generic way of resolving tags! thats quite obscure for modelconfig applications! -> TRA->getTags() maybe?
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        parent::_resolveRecords($_records);
    }
}