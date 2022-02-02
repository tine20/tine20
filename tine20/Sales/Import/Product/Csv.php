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
class Sales_Import_Product_Csv extends Tinebase_Import_Csv_Generic
{

    protected $_additionalOptions = array(
        'container_id' => '',
        'dates'        => array('lifespan_start','lifespan_end')
    );

    protected $localizedFields = [];
    protected $defaultLanguage = '';

    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $mc = Sales_Model_Product::getConfiguration();
        /** @var Tinebase_Config_KeyField $kFld */
        $kFld = Tinebase_Config::factory($mc->{Sales_Model_Product::LANGUAGES_AVAILABLE}[Sales_Model_Product::CONFIG]
            [Sales_Model_Product::APP_NAME])->{$mc->{Sales_Model_Product::LANGUAGES_AVAILABLE}[Sales_Model_Product::NAME]};
        $this->defaultLanguage = $kFld->default;

        foreach ($mc->fields as $key => $field) {
            if ($field[Sales_Model_Product::TYPE] !== Sales_Model_Product::TYPE_RECORDS ||
                $field[Sales_Model_Product::CONFIG][Sales_Model_Product::RECORD_CLASS_NAME] !==
                Sales_Model_ProductLocalization::class) {
                continue;
            }
            $this->localizedFields[] = $key;
        }
    }

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        foreach ($this->localizedFields as $field) {
            if (isset($result[$field])) {
                $result[$field] = [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => $this->defaultLanguage,
                    Sales_Model_ProductLocalization::FLD_TEXT => $result[$field],
                ]];
            }
        }

        return $result;
    }
}
