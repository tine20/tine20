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
     * @return integer
     */
    public function create_auto_invoices($_opts)
    {
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
                        $date = new Tinebase_DateTime($args['day']);
                    } catch (Exception $e) {
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
        
        Sales_Controller_Invoice::getInstance()->createAutoInvoices($date);
    }
}
