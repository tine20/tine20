<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * DeleteUsedBillingAddress exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_DeleteUsedBillingAddress extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Address as Billing Address in Use'; // _('Address as Billing Address in Use')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The Address you tried to delete is used in one or more contract(s) as Billing Address. Please assign another Billing Address to these contracts or change this one and do not delete.'; // _('The Address you tried to delete is used in one or more contract(s) as Billing Address. Please assign another Billing Address to these contracts or change this one and do not delete.')
    
    /**
     * @see SPL Exception
     */
    protected $code = 917;
    
    /**
     * contracts with this billing address assigned
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_contracts = NULL;
    
    public function setContracts(Tinebase_Record_RecordSet $contracts)
    {
        $this->_contracts = $contracts;
        $this->_contracts->sort('number');
    }
    
    public function toArray()
    {
        $convert = new Tinebase_Convert_Json();
        $contracts = $convert->fromTine20RecordSet($this->_contracts);
        
        return array('contracts' => $contracts);
    }
}
