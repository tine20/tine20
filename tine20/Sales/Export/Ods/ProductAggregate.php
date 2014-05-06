<?php
/**
 * Sales ProductAggregate Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales ProductAggregate Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
class Sales_Export_Ods_ProductAggregate extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'productaggregate_default_ods';

    /**
     * get record relations
     *
     * @var boolean
     */
    protected $_getRelations = FALSE;

    /**
     * all addresses (Sales_Model_Address) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_specialFields = array();

    /**
     * all contacts (Addressbook_Model_Contact) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contacts = NULL;
    
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
        $this->_userStyles[] = '<number:date-style style:name="germanDate" number:language="de" number:country="DE" number:automatic-order="true"><number:day number:style="long"/><number:text>.</number:text><number:month number:style="long"/><number:text>.</number:text><number:year number:style="long"/></number:date-style>';
        $this->_userStyles[] = '<style:style style:name="germanDateCell" style:family="table-cell" style:parent-style-name="Default" style:data-style-name="germanDate"/>';
        
        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * add body rows
     * 
     * @alternate, kind of POC or VIP, overwrites the default one
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function processIteration($_records)
    {
        $json = new Tinebase_Convert_Json();
        $resolved = $json->fromTine20RecordSet($_records);
        
        foreach ($resolved as $record) {
        
            $row = $this->_activeTable->appendRow();
            
            $i18n = $this->_translate->getAdapter();
            
            foreach ($this->_config->columns->column as $field) {
        
                $identifier = $field->identifier;
                
                // TODO: use ModelConfig here to get the POC
                // get type and value for cell
                $cellType = $this->_getCellType($field->type);
                
                switch ($identifier) {
                    case 'quantity':
                        $value = $record[$identifier];
                        break;
                    default:
                        $value = $record['product_id'][$identifier];
                } 
                
                // create cell with type and value and add style
                $cell = $row->appendCell($value, $cellType);
                
                if ($field->customStyle) {
                    $cell->setStyle((string) $field->customStyle);
                }
            }
            $i++;
        }
    }
}
