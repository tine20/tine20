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
    protected $_gebietsstand = null;

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
           if (null === $this->_gebietsstand && strpos($value, 'Gebietsstand') !== false &&
                    preg_match('/Gebietsstand.*(\d\d)\.(\d\d)\.(\d\d\d\d)/', $value, $m)) {
               $this->_gebietsstand = new Tinebase_DateTime($m[3] . '-' . $m[2] . '-' . $m[1]);
               $this->_gebietsstand->hasTime(false);
           }
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
        static $filter = null;
        if (null === $filter) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_MunicipalityKey::class, [
                ['field' => Tinebase_Model_MunicipalityKey::FLD_ARS_COMBINED, 'operator' => 'equals', 'value' => '']
            ]);
        }
        if ($_record->satzArt && is_numeric($_record->satzArt)) {
            if (null === $this->_gebietsstand) {
                throw new Tinebase_Exception_UnexpectedValue('could not find Gebietsstand in import data and none provided by importer');
            }
            $_record->{Tinebase_Model_MunicipalityKey::FLD_ARS_COMBINED} =
                $_record->arsLand . $_record->arsRB . $_record->arsKreis . $_record->arsVB . $_record->arsGem;
            $_record->{Tinebase_Model_MunicipalityKey::FLD_GEBIETSSTAND} = $this->_gebietsstand;
            $filter->getFilter(Tinebase_Model_MunicipalityKey::FLD_ARS_COMBINED)->setValue(
                $_record->{Tinebase_Model_MunicipalityKey::FLD_ARS_COMBINED}
            );
            if (null !== ($existingRecord = Tinebase_Controller_MunicipalityKey::getInstance()->search($filter)->getFirstRecord())) {
                $existingRecord->merge($_record);
                $_record = $existingRecord;
            }

            return parent::_importRecord($_record);
        }

        return null;
    }
}
