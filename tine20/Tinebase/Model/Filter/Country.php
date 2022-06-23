<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Country
 * 
 * filters for countries
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Country extends Tinebase_Model_Filter_Text
{
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false): array
    {
        $result = parent::toArray($_valueToJson);
        if (in_array($this->_operator, ['in', 'notin'])) {
            $expandedCountries = [];
            $countries = Tinebase_Translation::getCountryList();
            foreach ($this->_value as $country) {
                $filtered = array_filter($countries['results'], function ($value) use ($country) {
                    return $value['shortName'] === $country;
                });
                $expandedCountries[] = array_pop($filtered);
            }
            $result['value'] = $expandedCountries;
        }

        return $result;
    }
}
