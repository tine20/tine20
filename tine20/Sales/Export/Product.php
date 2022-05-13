<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Sales_Export_Product
 */
class Sales_Export_Product extends Tinebase_Export_CsvNew
{
    // TODO support name as sort field
    protected $_sortInfo = ['sort' => ['salesprice']];

    /**
     * @param mixed $_value
     * @return string
     */
    protected function _convertToString($_value)
    {
        if ($_value instanceof Tinebase_Record_RecordSet && $_value->getRecordClassName() === Sales_Model_ProductLocalization::class) {
            // TODO generalize & use default language if not in config
            $lang = $this->_config->language ?: 'en';
            $langValue = $_value->filter('language', $lang)->getFirstRecord();
            return $langValue ? $langValue->text : '';
        } else {
            return parent::_convertToString($_value);
        }
    }
}
