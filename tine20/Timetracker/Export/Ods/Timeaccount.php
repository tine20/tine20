<?php
/**
 * Timeaccount Ods generation class
 *
 * @package     Timetracker
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Timetracker Ods generation class
 * 
 * @package     Timetracker
 * @subpackage  Export
 */
class Timetracker_Export_Ods_Timeaccount extends Tinebase_Export_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * sort records by this field / dir
     *
     * @var array
     */
    protected $_sortInfo = array(
        'sort'  => 'start_date',
        'dir'   => 'DESC'
    );
    
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array('created_by');
    
    /**
     * resolve records
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        Tinebase_User::getInstance()->resolveMultipleUsers($_records, 'created_by', true);
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
        
        $value = '';
        
        switch($_param['type']) {
            case 'created_by':
                $value = $_record->$_param['type']->$_param['field'];
                break;
        }        
        return $value;
    }
}
