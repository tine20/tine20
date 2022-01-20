<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Import class for the Crm
 * 
 * @package     Crm
 * @subpackage  Import
 */
class Tinebase_Import_MunicipalityKey extends Tinebase_Import_Xls_Abstract
{
    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
    }

    /**
     * map data to the fields of the mapping array. We don't have a headline and therefor have to use the order
     * 
     * @param array $_data
     * @return array
     */
   public function _doMapping($_data)
   {
       $data = [];
       foreach ($_data as $key => $value) {
           if (isset($this->_options['mapping']['field'][$key]) && $this->_options['mapping']['field'][$key]['destination']) {
               $data[$this->_options['mapping']['field'][$key]['destination']] = $value;
           }
       }
       
       return $data;
   }

    /**
     * skip records that don't have a satzArt and therefor are no valid records
     * (the import file contains text elements in the table)
     * 
     * @param Tinebase_Record_Interface $_record
     * @param null $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface|null
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array()) 
    {
        if ($_record->satzArt && is_numeric($_record->satzArt)) {
            $_record->arsCombined = $_record->arsLand . $_record->arsRB . $_record->arsKreis . $_record->arsVB . $_record->arsGem;
            return parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        }

        return null;
    }
}
