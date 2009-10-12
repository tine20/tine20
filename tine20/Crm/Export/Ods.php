<?php
/**
 * Crm Ods generation class
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
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
     * @var string application of this export class
     */
    protected $_applicationName = 'Crm';
    
    /**
     * resolved records
     *
     * @var array of Tinebase_Record_RecordSet
     */
    //protected $_resolvedRecords = array();
    
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array(/*'created_by'*/);
    
    /**
     * export leads to Ods file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return string filename
     */
    public function generate(Crm_Model_LeadFilter $_filter) {
        
        // get leads by filter
        $leads = Crm_Controller_Lead::getInstance()->search($_filter);
        $lastCell = count($leads) + $this->_firstRow - 1;
        
        //Tinebase_User::getInstance()->resolveMultipleUsers($leads, 'account_id', true);
        
        // build export table
        $table = $this->getBody()->appendTable('Leads');        
        $this->_addHead($table, $this->_config['leads']);
        $this->_addBody($table, $leads, $this->_config['leads']);
        $this->_addFooter($table, $lastCell);
        
        // create file
        $filename = $this->getDocument();        
        return $filename;
    }
    
    /**
     * add table footer (formulas, ...)
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param integer $lastCell
     */
    protected function _addFooter($table, $lastCell)
    {
        // add footer
        /*
        $row = $table->appendRow();
        $row = $table->appendRow();
        $numberOfEmptyCells = ord($this->_config['leads']['sumColumn']) - 66;
        for ($i=0; $i<$numberOfEmptyCells; $i++) {
            $row->appendCell('string');
        }

        $row->appendCell('string', $this->_translate->_('Total Sum'));
        $cell = $row->appendCell('float', 0);
        // set sum for timesheet duration (for example E2:E10)
        $cell->setFormula('oooc:=SUM(' . $this->_config['leads']['sumColumn'] . $this->_firstRow . ':' . $this->_config['leads']['sumColumn'] . $lastCell . ')');   
        $cell->setStyle('ceBold');
        */     
    }
    
    /**
     * get export config
     *
     * @return array
     */
    protected function _getExportConfig()
    {
        $exportConfig = Tinebase_Config::getInstance()->getConfigAsArray(
            Tinebase_Model_Config::ODSEXPORTCONFIG, 'Crm', array('leads' => array(
            'header' => array(
                '{date}', 
                '{user}',
            ),
            'fields' => array(
                'start' => array(
                    'header'    => $this->_translate->_('Date'),
                    'type'      => 'date', 
                    'width'     => '2,5cm'
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                    'width'     => '10cm'
                ),
                'lead_name' => array(
                    'header'    => $this->_translate->_('Lead Name'),
                    'type'      => 'string', 
                    'width'     => '5cm',
                ),
            )
        )));

        return $exportConfig;
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
        /*
    	if (is_null($key)) {
    		throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
    	}
    	
        $value = '';
        
        switch($_param['type']) {
            case 'timeaccount':
                $value = $this->_resolvedRecords['timeaccounts'][$this->_resolvedRecords['timeaccounts']->getIndexById($_record->timeaccount_id)]->$_param['field'];
                break;
            case 'account_id':
            case 'created_by':
                $value = $_record->$_param['type']->$_param['field'];
                break;
        }        
        return $value;
        */
    }
}
