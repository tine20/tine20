<?php
/**
 * abstract class to auto set number
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo: make this more general (tinebase)
 *
 */

abstract class Sales_Controller_NumberableAbstract extends Tinebase_Controller_Record_Abstract {
    
    /**
     * Checks if number is unique if manual generated
     * 
     * @param Tinebase_Record_Interface $record
     * @param Boolean $update true if called un update
     * @throws Tinebase_Exception_Duplicate
     * @return boolean
     */
    protected function _checkNumberUniquity($record, $update = false)
    {
        $filterArray = array(
            array('field' => 'number', 'operator' => 'equals', 'value' => $record->number)
        );
        
        if ($update) {
            $filterArray[] = array('field' => 'id', 'operator' => 'notin', 'value' => $record->getId());
        }
        
        $filterName = $this->_modelName . 'Filter';
        $filter = new $filterName($filterArray);
        $existing = $this->search($filter);
    
        if (count($existing->toArray()) > 0) {
            $e = new Tinebase_Exception_Duplicate(_('The number you have tried to set is already in use!'));
            $e->setData($existing);
            $e->setClientRecord($record);
            throw $e;
        }
        
        return true;
    }
    
    /**
     * adds the next available number to the record
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _addNextNumber($record)
    {
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext($this->_modelName, Tinebase_Core::getUser()->getId());
        $record->number = intval($number->number);
    }
}
