<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * csv import class for the addressbook
 * 
 * @package     Addressbook
 * @subpackage  Import
 */
class Addressbook_Import_Csv extends Tinebase_Import_Csv_Abstract
{    
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_config
     * @return Calendar_Import_Ical
     * 
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_config = array())
    {
        return new Addressbook_Import_Csv(self::getConfigArrayFromDefinition($_definition, $_config));
    }

    /**
     * constructs a new importer from given config
     * 
     * @param array $_config
     */
    public function __construct(array $_config = array())
    {
        parent::__construct($_config);
        
        // don't set geodata for imported contacts as this is too much traffic for the nominatim server
        $this->_controller->setGeoDataForContacts(FALSE);
        
        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = $this->_controller->getDefaultAddressbook();
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
