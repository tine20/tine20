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
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array('created_by');
    
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
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($leads->toArray(), TRUE));
        
        Tinebase_User::getInstance()->resolveMultipleUsers($leads, 'created_by', true);
        
        // build export table
        $table = $this->getBody()->appendTable('Leads');        
        $this->_addHead($table, $this->_config['leads']);
        $this->_addBody($table, $leads, $this->_config['leads']);
        
        // create file
        $filename = $this->getDocument();        
        return $filename;
    }
    
    /**
     * get export config
     *
     * @return array
     * 
     * @todo add more fields
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
                'lead_name' => array(
                    'header'    => $this->_translate->_('Lead Name'),
                    'type'      => 'string', 
                    'width'     => '5cm',
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                    'width'     => '10cm'
                ),
                'turnover' => array(
                    'header'    => $this->_translate->_('Turnover'),
                    'type'      => 'string', 
                    'width'     => '2cm'
                ),
                'probability' => array(
                    'header'    => $this->_translate->_('Probability'),
                    'type'      => 'string', 
                    'width'     => '2cm'
                ),
                'start' => array(
                    'header'    => $this->_translate->_('Date Start'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'end' => array(
                    'header'    => $this->_translate->_('Date End'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'end_scheduled' => array(
                    'header'    => $this->_translate->_('Date End Scheduled'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'created_by' => array(
                    'header'    => $this->_translate->_('Created By'),
                    'type'      => 'created_by', 
                    'field'     => 'accountDisplayName', 
                    'width'     => '4cm'
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
