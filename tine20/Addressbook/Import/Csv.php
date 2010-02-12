<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 *

/**
 * csv import class for the addressbook
 * 
 * @package     Addressbook
 * @subpackage  Import
 * 
 */
class Addressbook_Import_Csv extends Tinebase_Import_Csv_Abstract
{    
    /**
     * the constructor
     *
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param mixed $_controller
     * @param array $_options additional options
     */
    public function __construct(Tinebase_Model_ImportExportDefinition $_definition, $_controller = NULL, $_options = array())
    {
        parent::__construct($_definition, $_controller, $_options);
        
        // don't set geodata for imported contacts as this is too much traffic for the nominatim server
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(FALSE);
        
        // get container id from default container if not set
        if (! isset($this->_options['container_id'])) {
            $defaultContainer = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();
            $this->_options['container_id'] = $defaultContainer->getId();
        }
    }
    
    /**
     * get filter for duplicate check
     * 
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getDuplicateSearchFilter(Tinebase_Record_Interface $_record)
    {
        $containerFilter = array('field' => 'container_id',    'operator' => 'equals', 'value' => $this->_options['container_id']);
        
        if (empty($_record->n_given) && empty($_record->n_family)) {
            // check organisation duplicates if given/fullnames are empty
            $filter = new Addressbook_Model_ContactFilter(array(
                $containerFilter,
                array('field' => 'org_name',        'operator' => 'equals', 'value' => $_record->org_name),
            ));
        } else {
            $filter = new Addressbook_Model_ContactFilter(array(
                $containerFilter,
                array('field' => 'n_given',         'operator' => 'equals', 'value' => $_record->n_given),
                array('field' => 'n_family',        'operator' => 'equals', 'value' => $_record->n_family),
            ));
        }
        
        return $filter;
    }
    
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }
    
    /**
     * do conversions
     * -> sanitize account_id
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        
        // unset account id
        if (isset($result['account_id']) && empty($result['account_id'])) {
            unset($result['account_id']);
        }
        
        return $result;
    }    
}