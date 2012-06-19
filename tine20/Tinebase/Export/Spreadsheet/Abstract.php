<?php
/**
 * Tinebase Abstract spreadsheet export class
 *
 * @package     Tinebase
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Tinebase Abstract spreadsheet export class
 * 
 * @package     Tinebase
 * @subpackage    Export
 */
abstract class Tinebase_Export_Spreadsheet_Abstract extends Tinebase_Export_Abstract
{
    /**
     * get export document object
     * 
     * @return Object the generated document
     */
    abstract public function getDocument();
    
    /**
     * get cell value
     * 
     * @param Zend_Config $_field
     * @param Tinebase_Record_Interface $_record
     * @param string $_cellType
     * @return string
     * 
     * @todo check string type for translated fields?
     * @todo add 'config' type again?
     * @todo move generic parts to Tinebase_Export_Abstract
     */
    protected function _getCellValue(Zend_Config $_field, Tinebase_Record_Interface $_record, &$_cellType)
    {
        $result = NULL;
        
        if (in_array($_field->type, $this->_specialFields)) {
            // special field handling
            $result = $this->_getSpecialFieldValue($_record, $_field->toArray(), $_field->identifier, $_cellType);
            $result = $this->_replaceAndMatchvalue($result, $_field);
            return $result;
            
        } else if (isset($field->formula) 
            || (! isset($_record->{$_field->identifier}) 
                && ! in_array($_field->type, $this->_resolvedFields) 
                && ! in_array($_field->identifier, $this->_getCustomFieldNames())
            )
        ) {
            // check if empty -> use alternative field
            if (isset($_field->empty)) {
                $fieldConfig = $_field->toArray();
                unset($fieldConfig['empty']);
                $fieldConfig['identifier'] = $_field->empty;
                $result = $this->_getCellValue(new Zend_Config($fieldConfig), $_record, $_cellType);
            }
            // don't add value for formula or undefined fields
            return $result;
        }
        
        switch($_field->type) {
            case 'datetime':
                $result = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_record->{$_field->identifier});
                // empty date cells, get displayed as 30.12.1899
                if(empty($result)) {
                    $result = NULL;
                }
                break;
            case 'date':
                $result = ($_record->{$_field->identifier} instanceof DateTime) ? $_record->{$_field->identifier}->toString('Y-m-d') : $_record->{$_field->identifier};
                // empty date cells, get displayed as 30.12.1899
                if (empty($result)) {
                    $result = NULL;
                }
                break;
            case 'tags':
                $result = $this->_getTags($_record);
                break;
            case 'currency':
                $currency = ($_field->currency) ? $_field->currency : 'EUR';
                $result =  ($_record->{$_field->identifier}) ? $_record->{$_field->identifier} : '0';
                $result .= ' ' . $currency;
                break;
            case 'percentage':
                $result    = $_record->{$_field->identifier} / 100;
                break;
            case 'container_id':
                $result = $this->_getContainer($_record, $_field->field, $_field->type);
                break;
                /*
            case 'config':
                $result = Tinebase_Config::getOptionString($_record, $_field->identifier);
                break;
                */
            case 'relation':
                $result = $this->_addRelations($_record, $_field->identifier, $_field->field);
                break;
            case 'notes':
                $result = $this->_addNotes($_record);
                break;
            default:
                if (in_array($_field->identifier, $this->_getCustomFieldNames())) {
                    // add custom fields
                    if (isset($_record->customfields[$_field->identifier])) {
                        $result = $_record->customfields[$_field->identifier];
                    }
                } elseif (isset($_field->divisor)) {
                    // divisor
                    $result = $_record->{$_field->identifier} / $_field->divisor;
                } elseif (in_array($_field->type, $this->_userFields) || in_array($_field->identifier, $this->_userFields)) {
                    // resolved user
                    $result = $this->_getUserValue($_record, $_field);
                } else if (is_object($_record->{$_field->identifier}) && method_exists($_record->{$_field->identifier}, '__toString')) {
                    // call __toString
                    $result = $_record->{$_field->identifier}->__toString();
                } else {
                    // all remaining
                    $result = $_record->{$_field->identifier};
                }
                
                if (isset($_field->trim) && $_field->trim == 1) {
                    $result = trim($result);
                }
                
                // set special value from params
                if (isset($_field->values)) {
                    $values = $_field->values->value->toArray();
                    if (isset($values[$result])) {
                        $result = $values[$result];
                    }
                }
                
                if (isset($_field->translate) && $_field->translate/* && $_cellType === OpenDocument_SpreadSheet_Cell::TYPE_STRING*/) {
                    $result = $this->_translate->_($result);
                }
                
                $result = $this->_replaceAndMatchvalue($result, $_field);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' field def: ' . print_r($_field->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' result: ' . $result);
        
        return $result;
    }
    
    /**
     * get value from resolved user record
     * 
     * @param Tinebase_Record_Interface $_record
     * @param Zend_Config $_fieldConfig
     */
    protected function _getUserValue($_record, $_fieldConfig)
    {
        $result = '';
        if (in_array($_fieldConfig->type, $this->_userFields)) {
            $user = $_record->{$_fieldConfig->type};
        } else {
            $user = $_record->{$_fieldConfig->identifier};
        }
        
        if (! empty($user) && is_object($user)) {
            if ($_fieldConfig->field) {
                $result = $user->{$_fieldConfig->field};
            } else {
                $result = $user->accountDisplayName;
            }
        }
        
        return $result;
    }
}
