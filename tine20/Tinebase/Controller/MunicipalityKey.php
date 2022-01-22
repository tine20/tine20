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
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        $communityNumber = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);
        return $this->aggregatePopulation($communityNumber);
    }


    /**
     * @param $_communityNumber
     * @return mixed
     */
    public function aggregatePopulation($_communityNumber)
    {
        if (!$_communityNumber->bevoelkerungGesamt) {
            $population = 0;
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_MunicipalityKey::class, [
                ['field' => 'arsCombined', 'operator' => 'startswith', 'value' => $_communityNumber->arsCombined]
            ]);
            $relatedCommunitys = $this->search($filter);
            
            foreach ($relatedCommunitys as $community) {
                $population += $community->bevoelkerungGesamt;
            }

            $_communityNumber->bevoelkerungGesamt = $population;
        }
        
        return $_communityNumber;
    }
}
