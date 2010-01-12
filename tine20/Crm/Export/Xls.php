<?php
/**
 * Crm xls generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 * @todo        add formulas / charts?
 * @todo        add class with common crm export functions (and move status/special field handling there)
 */

/**
 * Crm xls generation class
 * 
 * @package     Crm
 * @subpackage  Export
 */
class Crm_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Crm';
    
    /**
     * export records to Xls file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return PHPExcel
     */
    public function generate(Crm_Model_LeadFilter $_filter)
    {
        return $this->_generate($_filter, Crm_Controller_Lead::getInstance(), 'lead_name', TRUE);
    }
    
    /**
     * get default export config
     * 
     * @return array
     * 
     * @todo    add column width again?
     */
    protected function _getDefaultConfig()
    {
        return array(
            'fields' => array(
                'lead_name' => array(
                    'header'    => $this->_translate->_('Lead Name'),
                    'type'      => 'string', 
                //    'width'     => '5cm',
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                //    'width'     => '10cm'
                ),
                'turnover' => array(
                    'header'    => $this->_translate->_('Turnover'),
                    'type'      => 'string', 
                //    'width'     => '2cm'
                ),
                'probability' => array(
                    'header'    => $this->_translate->_('Probability'),
                    'type'      => 'string', 
                //    'width'     => '2cm'
                ),
                'start' => array(
                    'header'    => $this->_translate->_('Date Start'),
                    'type'      => 'datetime', 
                //    'width'     => '2,5cm'
                ),
                'end' => array(
                    'header'    => $this->_translate->_('Date End'),
                    'type'      => 'datetime', 
                //    'width'     => '2,5cm'
                ),
                'end_scheduled' => array(
                    'header'    => $this->_translate->_('Date End Scheduled'),
                    'type'      => 'datetime', 
                //    'width'     => '2,5cm'
                ),
                'created_by' => array(
                    'header'    => $this->_translate->_('Created By'),
                    'type'      => 'user', 
                //    'width'     => '4cm'
                ),
                'creation_time' => array(
                    'header'    => $this->_translate->_('Creation Time'),
                    'type'      => 'datetime', 
                //    'width'     => '4cm'
                ),
                'last_modified_by' => array(
                    'header'    => $this->_translate->_('Last modified By'),
                    'type'      => 'user', 
                //    'width'     => '4cm'
                ),
                'last_modified_time' => array(
                    'header'    => $this->_translate->_('Last modified'),
                    'type'      => 'datetime', 
                //    'width'     => '4cm'
                ),
                'leadstate' => array(
                    'header'    => $this->_translate->_('Leadstate'),
                    'type'      => 'config', 
                ),
                'leadsource' => array(
                    'header'    => $this->_translate->_('Leadsource'),
                    'type'      => 'config', 
                ),
                'leadtype' => array(
                    'header'    => $this->_translate->_('Leadtype'),
                    'type'      => 'config', 
                ),
                'PARTNER' => array(
                    'header'    => $this->_translate->_('Partner'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'CUSTOMER' => array(
                    'header'    => $this->_translate->_('Customer'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'RESPONSIBLE' => array(
                    'header'    => $this->_translate->_('Responsible'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'TASK' => array(
                    'header'    => $this->_translate->_('Task'),
                    'type'      => 'relation',
                    'field'     => 'summary',
                ),
                'PRODUCT' => array(
                    'header'    => $this->_translate->_('Product'),
                    'type'      => 'relation',
                    'field'     => 'name',
                ),           
                'status' => array(
                    'header'    => $this->_translate->_('Status'),
                    'type'      => 'special',
                ),
                'notes' => array(
                    'header'    => $this->_translate->_('History'),
                    'type'      => 'notes',
                ),
            ),
            //'template' => 'lead_test_template.xls'
        );
    }
    
    /**
     * special field value function
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @return string
     */
    protected function _addSpecialValue(Tinebase_Record_Abstract $_record, $_fieldName)
    {
        $result = '';
        switch ($_fieldName) {
            case 'status':
                $result = $_record->getLeadStatus();
                break;
        }
        
        return $result;
    }
}
