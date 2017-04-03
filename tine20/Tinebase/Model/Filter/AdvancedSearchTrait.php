<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Filter_AdvancedSearchTrait
 *
 * trait to share advanced search filter between abstract filter and query filter extending filter group
 *
 * @package     Tinebase
 * @subpackage  Filter
 */

trait Tinebase_Model_Filter_AdvancedSearchTrait {

    /**
     * append relation filter
     *
     * @param string $ownModel
     * @param array $relationsToSearchIn
     * @return Tinebase_Model_Filter_Id
     */
    protected function _getAdvancedSearchFilter($ownModel = null, $relationsToSearchIn = null)
    {
        if (  Tinebase_Core::get('ADVANCED_SEARCHING') ||
            ! Tinebase_Core::getPreference()->getValue(Tinebase_Preference::ADVANCED_SEARCH, false) ||
            empty($relationsToSearchIn))
        {
            return null;
        }

        if (0 === strpos($this->_operator, 'not')) {
            $not = true;
            $operator = substr($this->_operator, 3);
        } else {
            $not = false;
            $operator = $this->_operator;
        }
        $ownIds = array();
        foreach ((array) $relationsToSearchIn as $relatedModel) {
            $filterModel = $relatedModel . 'Filter';
            // prevent recursion here
            // TODO find a better way for this, maybe we could pass this an option to all filters in filter model
            Tinebase_Core::set('ADVANCED_SEARCHING', true);
            $relatedFilter = new $filterModel(array(
                array('field' => 'query',   'operator' => $operator, 'value' => $this->_value),
            ));

            try {
                $relatedIds = Tinebase_Core::getApplicationInstance($relatedModel)->search($relatedFilter, NULL, FALSE, TRUE);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                continue;
            }
            Tinebase_Core::set('ADVANCED_SEARCHING', false);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found ' . count($relatedIds) . ' related ids');

            $relationFilter = new Tinebase_Model_RelationFilter(array(
                array('field' => 'own_model'    , 'operator' => 'equals', 'value' => $relatedModel),
                array('field' => 'own_backend'  , 'operator' => 'equals', 'value' => 'Sql'),
                array('field' => 'own_id'       , 'operator' => 'in'    , 'value' => $relatedIds),
                array('field' => 'related_model', 'operator' => 'equals', 'value' => $ownModel)
            ));
            $ownIds = array_merge($ownIds, Tinebase_Relations::getInstance()->search($relationFilter, NULL)->related_id);
        }

        return new Tinebase_Model_Filter_Id('id', $not?'notin':'in', $ownIds);
    }
}