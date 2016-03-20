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
    public static function getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param,
                                                $_key = NULL,
                                                &$_cellType = NULL,
                                                $_resolvedRecords = NULL)
    {
        if (is_null($_key)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
        }

        $configKeysAndProps = array(
            'status' => array(
                'config' => Crm_Config::LEAD_STATES,
                'property' => 'leadstate_id',
            ),
            'source' => array(
                'config' => Crm_Config::LEAD_SOURCES,
                'property' => 'leadsource_id',
            ),
            'type'   => array(
                'config' => Crm_Config::LEAD_TYPES,
                'property' => 'leadtype_id',
            ),
        );

        switch ($_param['type']) {
            case 'status':
            case 'source':
            case 'type':
                $value = Crm_Config::getInstance()->get(
                    $configKeysAndProps[$_param['type']]['config']
                )->getTranslatedValue(
                    $_record->{$configKeysAndProps[$_param['type']]['property']}
                );
                break;
            case 'open_tasks':
                $value = 0;
                foreach ($_record->relations as $relation) {
                    // check if is task and open
                    if ($relation->type == 'TASK') {
                        $idx = $_resolvedRecords['tasksStatus']->getIndexById($relation->related_record->status);
                        if ($idx) {
                            $status = $_resolvedRecords['tasksStatus'][$idx];
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
