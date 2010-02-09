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
     * get record relations
     * 
     * @var boolean
     */
    protected $_getRelations = TRUE;    
    
    /**
     * constructor (adds more values with Crm_Export_Helper)
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param array $_additionalOptions
     * @return void
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        parent::__construct($_filter, $_controller, $_additionalOptions);
        
        $this->_specialFields = Crm_Export_Helper::getSpecialFields();
        $this->_resolvedRecords = Crm_Export_Helper::getResolvedRecords();
    }
    
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
        return Crm_Export_Helper::getSpecialFieldValue($_record, $_param, $_key, $_cellType, $this->_resolvedRecords);
    }
        
    /**
     * get default export config
     * 
     * @return array
     * 
     * @todo    add relations again?
     */
    /*
    protected function _getDefaultConfig()
    {
        return array(
            'fields' => array(
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
