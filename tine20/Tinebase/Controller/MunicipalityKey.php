<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for MunicipalityKey
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_MunicipalityKey extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_MunicipalityKey::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_MunicipalityKey::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_MunicipalityKey::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);

        $this->_duplicateCheckFields = Tinebase_Config::getInstance()->get(Tinebase_Config::MUNICIPALITYKEY_DUP_FIELDS, array(
            array('arsCombined')
        ));
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::get()
     * @return Tinebase_Model_MunicipalityKey
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        /** @var Tinebase_Model_MunicipalityKey $communityNumber */
        $communityNumber = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted, $_aclProtect);
        return $this->aggregatePopulation($communityNumber);
    }


    /**
     * @param Tinebase_Model_MunicipalityKey $_municipality
     * @return mixed
     */
    public function aggregatePopulation(Tinebase_Model_MunicipalityKey $_municipality)
    {
        if (null === $_municipality->{Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNG_GESAMT}) {
            $population = 0;
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_MunicipalityKey::class, [
                ['field' => 'arsCombined', 'operator' => 'startswith', 'value' => $_municipality->arsCombined],
                ['field' => 'arsCombined', 'operator' => 'not', 'value' => $_municipality->arsCombined],
            ]);
            $relatedCommunitys = $this->search($filter);
            
            foreach ($relatedCommunitys as $community) {
                $population += (int)$community->{Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNG_GESAMT};
            }

            $_municipality->{Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNG_GESAMT} = $population;
        }
        
        return $_municipality;
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        if ((int)$updatedRecord->{Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNG_GESAMT} !==
                (int)$currentRecord->{Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNG_GESAMT}) {
            Tinebase_Event::fireEvent(new Tinebase_Event_Record_Update(['observable' => $updatedRecord]));
        }
    }


    /**
     * Municipality Key might have a zero fill: 
     * we need to cut off the individual parts, if they are only zeros and check he remaining key for a match
     * 
     * Gem length 3
     * VB length 4
     * Kreis length 2
     * RB length 1
     * 
     * @param string $key
     * @return Tinebase_Record_Interface|NULL
     */
    public function findMunicipalityKey(string $key)
    {
        $result = $this->_searchMunicipalityKey($key);
        if (!$result) {
            $pattern = ['000','0000','00','0'];
            
            foreach ($pattern as $part) {
                if (substr($key, -strlen($part)) == $part) {
                    $key = substr($key, 0, strlen($key) - strlen($part));
                    $result = $this->_searchMunicipalityKey($key);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param string $_key
     * @return Tinebase_Record_Interface|NULL
     */
    private function _searchMunicipalityKey(string $_key)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_MunicipalityKey::class, [
            ['field' => 'arsCombined', 'operator' => 'equals', 'value' => $_key]
        ]);
        $municipalityKey = Tinebase_Controller_MunicipalityKey::getInstance()->search($filter)->getFirstRecord();
        return $municipalityKey;
    }
}
