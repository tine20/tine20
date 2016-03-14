<?php
/**
 * Crm 
 *
 * @package     Crm
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Crm Ods generation class
 * 
 * @package     Crm
 * @subpackage    Export
 * 
 */
class Crm_Export_Helper
{
    /**
     * get special fields for export
     * 
     * @return array
     */
    public static function getSpecialFields()
    {
        return array('status', 'source', 'type', 'open_tasks');
    }
    
    /**
     * get resolved records (task status, ...)
     * 
     * @return array
     */
    public static function getResolvedRecords()
    {
        $result = array();
        $result['tasksStatus'] = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records;
        
        return $result;
    }
    
    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $_key
     * @param string $_cellType
     * @param array $_resolvedRecords
     * @return string
     */
    public static function getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL, $_resolvedRecords = NULL)
    {
        if (is_null($_key)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
        }

        switch($_param['type']) {
            case 'status':
                // TODO what about status? there is something missing here ... the code should be generalized for status/source/type
            case 'source':
                $source = Crm_Config::getInstance()->get(Crm_Config::LEAD_SOURCES)->getTranslatedValue($_record->leadsource_id);
                if (isset($source)) {
                    $value = $source;
                } else {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Leadsource id not found:' . $_record->leadsource_id);
                    $value = '';
                }
                break;
            case 'type':
                $type = Crm_Config::getInstance()->get(Crm_Config::LEAD_TYPES)->getTranslatedValue($_record->leadtype_id);
                if (isset($type['leadtype'])) {
                    $value = $type;
                } else {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . $leadIdType . ' id not found:' . $_record->{$leadIdType . '_id'});
                    $value = '';
                }
                break;
            case 'open_tasks':
                $value = 0;
                foreach ($_record->relations as $relation) {
                    // check if is task and open
                    if ($relation->type == 'TASK') {
                        $idx = $_resolvedRecords['tasksStatus']->getIndexById($relation->related_record->status);
                        if ($idx) {
                            $status = $_resolvedRecords['tasksStatus'][$idx];
                            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($status->toArray(), TRUE));
                            if ($status->is_open) {
                                $value++;
                            }
                        }
                    }
                }
                break;
            default:
                $value = '';
        }

        return $value;
    }
}
