<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Record Dehydrator Strategy, defining what to do
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Dehydrator_Strategy
{
    const DEF_LOAD_FROM_MODEL = 'loadFromModel';
    const DEF_FLAT = 'flat';
    const DEF_SUB_DEFINITIONS = 'subDefinitions';

    protected $_preSubHTasks = [];
    protected $_postSubHTasks = [];
    protected $_subHydrators = [];

    protected $_type;
    protected $_definition = [];
    protected $_subDefinitions = [];

    public function __construct($_type, array $_definition = null)
    {
        $this->_type = $_type;
        if (null !== $_definition) {
            $this->_definition = $_definition;
            if (isset($this->_definition[self::DEF_SUB_DEFINITIONS])) {
                $this->_subDefinitions = $this->_definition[self::DEF_SUB_DEFINITIONS];
            }
        }
    }

    /**
     * @param $_model
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     */
    public function loadFromModel($_model)
    {
        /*if (isset($this->_definition[self::DEF_LOAD_FROM_MODEL]) && !$this->_definition[self::DEF_LOAD_FROM_MODEL]) {
            return;
        }*/

        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }

        $propertyKeys = [];
        $dateFields = [];
        $dateTimeFields = [];
        $userTimeZone = Tinebase_Core::getUserTimezone();

        foreach ($mc->getFields() as $property => $definition) {
            // TODO check if field should be send to FE / Backend
            // if not, continue, that is why we use our own date and datetime field array
            $propertyKeys[$property] = null;

            if (isset($definition['type'])) {
                switch ($definition['type']) {
                    case 'user':
                    case 'record':
                    case 'records':
                    case 'relation':
                    case 'tag':
                    case 'attachments':
                    case 'note':
                    case 'container':
                        if (isset($this->_definition[self::DEF_FLAT]) && $this->_definition[self::DEF_FLAT] &&
                                !isset($this->_subDefinitions[$property])) {
                            break;
                        }
                        if (null === ($model = $mc->getFieldModel($property))) {
                            throw new Tinebase_Exception_NotImplemented($_model . '::' . $property .
                                ' has a unknown model');
                        }
                        $this->_subHydrators[$property] = Tinebase_Record_Hydration_Factory::createDehydrator(
                            $this->_type, $model, isset($this->_subDefinitions[$property]) ?
                                $this->_subDefinitions[$property] : null);
                        break;
                    case 'date':
                        $dateFields[] = $property;
                        break;
                    case 'datetime':
                        $dateTimeFields[] = $property;
                        break;
                }
            }
        }

        $this->_preSubHTasks[] = function(&$_data) use($propertyKeys) {
            $_data = array_intersect_key($_data, $propertyKeys);
        };
        if (!empty($dateFields)) {
            $this->_preSubHTasks[] = function(&$_data) use($dateFields) {
                foreach ($dateFields as $property) {
                    /** @var Tinebase_DateTime $date */
                    if (isset($_data[$property]) && ($date = $_data[$property]) instanceof Tinebase_DateTime) {
                        $_data[$property] = $date->format('Y-m-d');
                    }
                }
            };
        }
        if (!empty($dateTimeFields)) {
            $this->_preSubHTasks[] = function(&$_data) use($dateTimeFields, $userTimeZone) {
                foreach ($dateTimeFields as $property) {
                    /** @var Tinebase_DateTime $date */
                    if (isset($_data[$property]) && ($date = $_data[$property]) instanceof Tinebase_DateTime) {
                        $_data[$property] = $date->getClone()->setTimezone($userTimeZone)->format('Y-m-d H:i:s');
                    }
                }
            };
        }
    }

    /**
     * @return array
     */
    public function getPreSubHTasks()
    {
        return $this->_preSubHTasks;
    }

    /**
     * @return array
     */
    public function getPostSubHTasks()
    {
        return $this->_postSubHTasks;
    }

    /**
     * @return array
     */
    public function getSubHydrators()
    {
        return $this->_subHydrators;
    }
}