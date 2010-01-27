<?php
/**
 * Crm Ods generation class
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add class with common crm export functions (and move status/special field handling there)
 * @todo        add relations / products / state / type / source
 */

/**
 * Crm Ods generation class
 * 
 * @package     Crm
 * @subpackage	Export
 * 
 */
class Crm_Export_Ods extends Tinebase_Export_Ods
{
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'lead_default_ods';
        
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Crm';
    
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array('created_by', 'status', 'duration', 'container_id');
    
    /**
     * resolve records
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        Tinebase_User::getInstance()->resolveMultipleUsers($_records, 'created_by', true);
        Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_records->toArray(), TRUE));
    }
    
    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $key
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $key = null)
    {
    	if (is_null($key)) {
    		throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
    	}
    	
        switch($_param['type']) {
            case 'created_by':
                $value = $_record->$_param['type']->$_param['field'];
                break;
            case 'container_id':
                $container = $_record->$_param['type']; 
                $value = $container[$_param['field']];
                break;
            case 'status':
                $value = $_record->getLeadStatus();
                break;
            case 'duration':
                if ($_record->end) {
                    $value = $_record->end->sub($_record->start, Zend_Date::DAY);
                } else {
                    $value = NULL;
                }
                break;
            default:
                $value = '';
        }        
        return $value;
    }
    
    /**
     * get name of data table
     * 
     * @return string
     */
    protected function _getDataTableName()
    {
        return 'Leads';        
    }
}
