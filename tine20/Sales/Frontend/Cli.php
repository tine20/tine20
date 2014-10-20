<?php
/**
 * Tine 2.0
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Sales
 *
 * This class handles cli requests for the Sales
 *
 * @package     Sales
 */
class Sales_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';
    
    protected $_help = array(
        'create_auto_invoices' => array(
            'description'   => 'Creates automatic invoices for contracts by their dates and intervals',
            'params' => array(
                'day'         => 'Day to work on',
                'contract'    => 'Contract ID or number to bill'
            )
        )
    );
    
    /**
     * creates missing accounts
     * 
     * * optional params:
     *   - day=YYYY-MM-DD
     *   - remove_unbilled=1
     *   - contract=CONTRACT_ID or contract=NUMBER
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function create_auto_invoices($_opts)
    {
        $executionLifeTime = Tinebase_Core::setExecutionLifeTime(3600*8);
        
        $this->_addOutputLogWriter();
        
        $date = NULL;
        $args = $this->_parseArgs($_opts, array());
    
        // if day argument is given, validate
        if (array_key_exists('day', $args)) {
            $split = explode('-', $args['day']);
            if (! count($split == 3)) {
                // failure
            } else {
                if ((strlen($split[0]) != 4) || (strlen($split[1]) != 2) || (strlen($split[2]) != 2)) {
                    // failure
                } elseif ((intval($split[1]) == 0) || (intval($split[2]) == 0)) {
                    // failure
                } else {
                    // other errors are caught by datetime
                    try {
                        
                        $date = new Tinebase_DateTime();
                        // use usertimezone
                        $date->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                        // if a date is given, set hour to 3
                        $date->setDate($split[0], $split[1], $split[2])->setTime(3,0,0);
                    } catch (Exception $e) {
                        Tinebase_Exception::log($e);
                    }
                }
            }
            if (! $date) {
                die('The day must have the following format: YYYY-MM-DD!' . PHP_EOL);
            }
        }
        
        if (! $date) {
            $date = Tinebase_DateTime::now();
            $date->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        }
        
        if (array_key_exists('remove_unbilled', $args) && $args['remove_unbilled'] == 1) {
            $this->removeUnbilledAutoInvoices();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating invoices for ' . $date->toString());
        }
        
        $contract = NULL;
        
        if (array_key_exists('contract', $args)) {
            try {
                $contract = Sales_Controller_Contract::getInstance()->get($args['contract']);
            } catch (Tinebase_Exception_NotFound $e) {
                $filter = new Sales_Model_ContractFilter(array(array('field' => 'number', 'operator' => 'equals', 'value' => $args['contract'])));
                $contract = Sales_Controller_Contract::getInstance()->search($filter, NULL, TRUE);
                if ($contract->count() == 1) {
                    $contract = $contract->getFirstRecord();
                } elseif ($contract->count() > 1) {
                    die('The number you have given is not unique! Please use the ID instead!');
                } else {
                    die('A contract could not be found!');
                }
            }
        }
        
        $result = Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            unset($result['created']);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, true));
        }
        
        Tinebase_Core::setExecutionLifeTime($executionLifeTime);
    }
    
    /**
     * transfers all contracts starting with AB- to orderconfirmation
     */
    public function transferContractsToOrderConfirmation()
    {
        $contractController = Sales_Controller_Contract::getInstance();
        $ocController = Sales_Controller_OrderConfirmation::getInstance();
        $rel = Tinebase_Relations::getInstance();
        
        $filter = new Sales_Model_ContractFilter(array(array('field' => 'number', 'operator' => 'startswith', 'value' => 'AB-')), 'AND');
        $contracts = $contractController->search($filter);
        
        foreach($contracts as $contract) {
            $oc = $ocController->create(new Sales_Model_OrderConfirmation(array(
                'number' => $contract->number,
                'title'  => $contract->title,
                'description' => '',
            )));
            
            $rel->setRelations('Sales_Model_OrderConfirmation', 'Sql', $oc->getId(), array(array(
                'own_degree' => 'sibling',
                'related_degree' => 'sibling',
                'related_model' => 'Sales_Model_Contract',
                'related_backend' => 'Sql',
                'related_id' => $contract->getId(),
                'type' => 'CONTRACT'
            )));
        }
    }
    
    /**
     * merge contracts into one contract and removes the old ones
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function mergeContracts(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array());
        
        if (! array_key_exists('target', $args)) {
            echo '"target" argument missing. A contract number is required!' . PHP_EOL;
            exit(1);
        } else {
            $target = trim($args['target']);
        }
        
        if (! array_key_exists('source', $args)) {
            echo '"source" argument missing. At least one contract number is required!' . PHP_EOL;
            exit(1);
        } else {
            $source = $args['source'];
        }
        
        $filter = new Sales_Model_ContractFilter(array(array('field' => 'number' , 'operator' => 'equals', 'value' => $target)));
        $targetContract = Sales_Controller_Contract::getInstance()->search($filter);
        if ($targetContract->count() == 1) {
            $targetContract = $targetContract->getFirstRecord();
        } else {
            if ($targetContract->count() > 1) {
                echo 'Target contract with the given number "' . $target . '" has ' . $targetContract->count() . ' results.' . PHP_EOL;
            } else {
                echo 'No target contract with the given number has been found.' . PHP_EOL;
            }
            exit(1);
        }
        
        if (strpos($source, ',')) {
            $sources = explode(',', $source);
        } else {
            $sources = array($source);
        }
        $sourceContracts = new Tinebase_Record_RecordSet('Sales_Model_Contract');
        foreach($sources as $source) {
            $filter = new Sales_Model_ContractFilter(array(array('field' => 'number' , 'operator' => 'equals', 'value' => $source)));
            $sourceContract = Sales_Controller_Contract::getInstance()->search($filter);
            
            if ($sourceContract->count() == 1) {
                $sourceContracts->addRecord($sourceContract->getFirstRecord());
            } else {
                if ($sourceContract->count() > 1) {
                    echo 'Source contract with the given number "' . $source . '" has ' . $sourceContract->count() . ' results.' . PHP_EOL;
                } else {
                    echo 'No source contract with the given number "' . $source . '" has been found.' . PHP_EOL;
                }
                
                exit(1);
            }
        }
        
        if (Sales_Controller_Contract::getInstance()->mergeContracts($targetContract, $sourceContracts)) {
            echo 'Contracts has been merged successfully!' . PHP_EOL;
        } else {
            echo 'Contracts merge failed!' . PHP_EOL;
        }
    }
    
    public function setLastAutobill()
    {
        $cc = Sales_Controller_Contract::getInstance();
        $pc = Sales_Controller_ProductAggregate::getInstance();
        
        $date = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $date->setDate($date->format('Y'), 1, 1)->setTime(0,0,0);
        $date->setTimezone('UTC');
        
        $filter = new Sales_Model_ContractFilter(array(
            array('field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $date)
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date', 'operator' => 'isnull', 'value' => NULL)));

        $contracts = $cc->search($filter);
        
        foreach($contracts as $contract) {
            $filter = new Sales_Model_ProductAggregateFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())));
            
            echo 'Updating last_autobill of ' . $contract->title . PHP_EOL;
            
            $contract->last_autobill = clone $contract->start_date;
            $contract->last_autobill->subMonth($contract->interval);
            
            foreach ($pc->search($filter) as $pagg) {
                echo 'Updating last_autobill of product assigned to ' . $contract->title . PHP_EOL;
                
                $pagg->last_autobill = clone $contract->start_date;
                $pagg->last_autobill->subMonth($pagg->interval);
                
                $pc->update($pagg);
            }
            
            $cc->update($contract);
        }
    }
    
    /**
     * removes unbilled auto invoices
     */
    public function removeUnbilledAutoInvoices()
    {
        $c = Sales_Controller_Invoice::getInstance();
        
        $f = new Sales_Model_InvoiceFilter(array(
                array('field' => 'is_auto', 'operator' => 'equals', 'value' => TRUE),
                array('field' => 'cleared', 'operator' => 'not', 'value' => 'CLEARED'),
        ));
        
        $p = new Tinebase_Model_Pagination(array('sort' => 'start_date', 'dir' => 'DESC'));
        
        $invoiceIds = $c->search($f, $p, /* $_getRelations = */ false, /* only ids */ true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' About to delete ' . count($invoiceIds) .' uncleared invoices ...');
        }
        
        foreach ($invoiceIds as $invoiceId) {
            try {
                $c->delete(array($invoiceId));
            } catch (Sales_Exception_DeletePreviousInvoice $sedpi) {
                Tinebase_Exception::log($sedpi);
            }
        }
    }
    
    /**
     * @see Sales_Controller_Contract.transferBillingInformation
     */
    public function transferBillingInformation()
    {
        Sales_Controller_Contract::getInstance()->transferBillingInformation();
    }
    
    /**
     * @see Sales_Controller_Contract.transferBillingInformation
     */
    public function updateBillingInformation()
    {
        Sales_Controller_Contract::getInstance()->transferBillingInformation(TRUE);
    }
    
    /**
     * sets start date and last_auobill by existing invoice positions / normalizes last_autobill
     */
    public function updateLastAutobillOfProductAggregates()
    {
        Sales_Controller_Contract::getInstance()->updateLastAutobillOfProductAggregates();
        
    }
}

