<?php
/**
 * Crm xls generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
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
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'lead_default_xls';
        
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array('status', 'source', 'type');
    
    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $_key
     * @param string $_cellType
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL)
    {
        return Crm_Export_Helper::getSpecialFieldValue($_record, $_param, $_key, $_cellType);
    }
    
    /**
     * export records to Xls file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return PHPExcel
     */
    /*
    public function generate(Crm_Model_LeadFilter $_filter)
    {
        return $this->_generate($_filter, Crm_Controller_Lead::getInstance(), 'lead_name', TRUE);
    }
    */
    
    /**
     * get default export config
     * 
     * @return array
     * 
     * @todo    add column width again?
     */
    /*
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
    */
}
