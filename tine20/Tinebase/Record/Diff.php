<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Record_Diff
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string id
 * @property string model
 * @property array  diff
 * @property array  oldData
 */
class Tinebase_Record_Diff extends Tinebase_Record_Abstract 
{
    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'model'             => array('allowEmpty' => TRUE),
        'diff'              => array('allowEmpty' => TRUE), // array of mismatching fields containing new data
        'oldData'           => array('allowEmpty' => TRUE),
        
        // @todo add base / compare records -> @see DateTime compare
    );
    
    /**
     * returns array with record related properties 
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $recordArray = parent::toArray($_recursive);
        if ($_recursive && isset($recordArray['diff'])) {
            foreach ($recordArray['diff'] as $property => $value) {
                if ($this->_hasToArray($value)) {
                    $recordArray['diff'][$property] = $value->toArray();
                }
            }
        }
        if ($_recursive && isset($recordArray['oldData'])) {
            foreach ($recordArray['oldData'] as $property => $value) {
                if ($this->_hasToArray($value)) {
                    $recordArray['oldData'][$property] = $value->toArray();
                }
            }
        }
        
        return $recordArray;
    }
    
    /**
     * is equal = empty diff
     * 
     * @param array $toOmit
     * @return boolean
     */
    public function isEmpty($toOmit = array())
    {
        if (count($toOmit) === 0) {
            if (! is_array($this->diff) || count($this->diff) === 0) {
                return (! is_array($this->oldData) || count($this->oldData) === 0);
            } else {
                return false;
            }
        }

        $diff = array_diff(array_keys($this->diff), $toOmit);
        
        return (count($diff) === 0 ? count(array_diff(array_keys($this->oldData), $toOmit)) === 0 : false);
    }

    /**
     * only empty values have been replaced
     *
     * @return boolean
     */
    public function onlyEmptyValuesInOldData()
    {
        $nonEmptyValues = array_filter($this->oldData, function($v) {
            return ! empty($v);
        });
        return count($nonEmptyValues) === 0;
    }

    public function purgeLonelySeq()
    {
        if (is_array($this->_properties['diff'])) {
            foreach ($this->_properties['diff'] as $key => $value) {
                if ($value instanceof Tinebase_Record_Diff || $value instanceof Tinebase_Record_RecordSetDiff) {
                    $value->purgeLonelySeq();
                    if ($value->isEmpty()) {
                        unset($this->_properties['diff'][$key]);
                        if (is_array($this->_properties['oldData'])) {
                            unset($this->_properties['oldData'][$key]);
                        }
                    }
                }
            }
            if (count($this->_properties['diff']) === 1 && isset($this->_properties['diff']['seq'])) {
                unset($this->_properties['diff']['seq']);
                if (is_array($this->_properties['oldData'])) {
                    unset($this->_properties['oldData']['seq']);
                }
            }
        }
    }
}
