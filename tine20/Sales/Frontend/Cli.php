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
                'day'         => ''
            )
        )
    );
    
    /**
     * creates missing accounts
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
            $dateOK = TRUE;
            $split = explode('-', $args['day']);
            if (! count($split == 3)) {
            } else {
                if ((strlen($split[0]) != 4) || (strlen($split[1]) != 2) || (strlen($split[2]) != 2)) {
                } elseif ((intval($split[1]) == 0) || (intval($split[2]) == 0)) {
                    // other errors are caught by datetime
                } else {
                    try {
                        $date = new Tinebase_DateTime();
                        $date->setDate($split[0], $split[1], $split[2]);
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
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating invoices for ' . $date->toString());
        }
        
        $result = Sales_Controller_Invoice::getInstance()->createAutoInvoices($date);
        
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
}
