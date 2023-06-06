<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Configuration
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_NewModelConfiguration
 *
 * @package     Tinebase
 * @subpackage  Configuration
 *
 */

class Tinebase_NewModelConfiguration  extends Tinebase_ModelConfiguration
{
    /**
     * This maps field types to their default converter
     *
     * @var array
     */
    protected $_converterDefaultMapping = array(
        'json'      => [Tinebase_Model_Converter_Json::class],
        'date'      => [Tinebase_Model_Converter_Date::class],
        'datetime'  => [Tinebase_Model_Converter_DateTime::class],
    );

    /**
     * the constructor (must be called in a singleton per model fashion, each model maintains its own singleton)
     *
     * @param array $modelClassConfiguration
     * @param string $recordClas
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function __construct($modelClassConfiguration,$recordClas)
    {
        try {
            parent::__construct($modelClassConfiguration, $recordClas);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw $tenf;
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_DefinitionFailure('exception: '
                . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function setValidators($_validators)
    {
        $this->_validators = $_validators;
        foreach ($this->_validators as $prop => $val) {
            if (!isset($this->_fields[$prop])) {
                $this->_fields[$prop] = [];
            }
        }
    }
}
