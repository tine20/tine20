<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Sales
 *
 * @package     Sales
 * @subpackage  Import
 *
 */
class Sales_Import_Customers_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );
    
    protected $_street;
    protected $_postc;
    protected $_local;
    protected $_ort;

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $this->_street = $result['street'];
        $this->_postc = $result['postnumber'];
        $this->_local = $result['postfach'];
        $this->_ort = $result['ort'];
        $users = Addressbook_Controller_Contact::getInstance()->getAll();
        foreach ($users as $user) {
            if ($user['n_fileas'] == $result['contract_ex']) {
                $result['cpextern_id'] = $user['id'];
            } elseif ($user['n_fileas'] == $result['contract_in']) {
                $result['cpintern_id'] = $user['id'];
            }
        }
        return $result;
    }
    
    protected function _inspectAfterImport($importedRecord)
    {
        parent::_inspectAfterImport($importedRecord);
        $record_address  = new Sales_Model_Address(array(
            'customer_id' => $importedRecord['id'],
            'street' =>  $this->_street,
            'postalcode' => $this->_postc,
            'locality' => $this->_local,
            'pobox' => $this->_ort
        ));
        Sales_Controller_Address::getInstance()->create($record_address);

    }

}