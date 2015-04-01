<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Purchase Invoice Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
class Sales_Export_Ods_PurchaseInvoice extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'purchaseinvoice_default_ods';

    /**
     * get record relations
     *
     * @var boolean
     */
    protected $_getRelations = TRUE;

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
     * add single body row
     *
     * @todo avoid code duplication with parent::processRecord
     * @param $record
     */
    public function processRecord($record, $idx)
    {
        $row = $this->_activeTable->appendRow();
        
        $supplier = null;
        $approver = null;
        
        if (isset($record->relations) && $record->relations instanceof Tinebase_Record_RecordSet) {
            foreach($record->relations as $relation) {
                switch ($relation->related_model) {
                    case 'Sales_Model_Supplier':
                        $supplier = $relation->related_record->number . ' - ' . $relation->related_record->name;
                        
                        break;
                        
                    case 'Addressbook_Model_Contact':
                        if ($relation->type == 'APPROVER') {
                            $approver = $relation->related_record->n_fileas;
                        }
                        break;
                }
            }
        }
        
        foreach ($this->_config->columns->column as $field) {
            // get type and value for cell
            $identifier = $field->identifier;
            switch ($identifier) {
                case 'approver':
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
                    $cellValue = $approver;
                    break;
                    
                case 'supplier':
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
                    $cellValue = $supplier;
                    break;
                
                case 'price_gross':
                case 'price_net':
                case 'price_tax':
                    $field->currenry = 'EUR';
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_CURRENCY;
                    $cellValue = $this->_getCellValue($field, $record, $cellType);
                    break;
                    
                default:
                    $cellType  = $this->_getCellType($field->type);
                    $cellValue = $this->_getCellValue($field, $record, $cellType);
                    break;
            }

            // create cell with type and value and add style
            $cell = $row->appendCell($cellValue, $cellType);
            
            if ($field->columnStyle) {
                $cell->setStyle((string) $field->columnStyle);
            }
            
            // add formula
            if ($field->formula) {
                $cell->setFormula($field->formula);
            }
        }
    }
}
