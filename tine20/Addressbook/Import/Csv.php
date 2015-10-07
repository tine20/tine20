<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
    );
    
    /**
     * creates a new importer from an importexport definition
     *
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_Ical
     *
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Addressbook_Import_Csv(self::getOptionsArrayFromDefinition($_definition, $_options));
    }

    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        // don't set geodata for imported contacts as this is too much traffic for the nominatim server
        $this->_controller->setGeoDataForContacts(FALSE);
        
        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = $this->_controller->getDefaultAddressbook();
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting default container id: ' . $this->_options['container_id']);
        }
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
        
        if (empty($result['n_family']) && empty($result['org_name'])) {
            if (isset($result['n_fn'])) {
                $result['n_family'] = $result['n_fn'];
            } else {
                $result['n_family'] = 'imported';
            }
        } 
        
        return $result;
    }
}
