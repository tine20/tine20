<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the addressbook
 * 
 * @package     Addressbook
 * @subpackage  Import
 *
 * @property Addressbook_Controller_Contact     $_controller    protected property!
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
     * constructs a new importer from given config
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        if (! Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_IMPORT_NOMINATIM)) {
            // don't set geodata for imported contacts as this might be too much traffic for the nominatim server
            // TODO make this setting overwritable via import definition/options
            $this->_controller->setGeoDataForContacts(FALSE);
        }

        //@todo option over cli method! -> switch to enable.
        // default only for demodata!
        if(isset($_options['demoData'])) {
            $this->_getNotes();
        }

        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = $this->_controller->getDefaultAddressbook();
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . ' Setting default container id: ' . $this->_options['container_id']);
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
     * -> sanitize account_id and n_family
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
            }
        } 
        
        return $result;
    }

    /**
     * add history notes to imported contacts
     */
    protected function _afterImport()
    {
        $controller = $this->_controller;
        if ($this->_options['demoData'] && $this->_additionalOptions['notes'] && $this->_importResult['results']) {
            foreach ($this->_importResult['results'] as $record) {
                $oldRecord = clone $record;
                foreach ($this->_additionalOptions['notes'] as $key => $note) {
                    $record->$key = $note;
                }
                /*
                 * update record and saves the old data
                 * update record with the old data
                 * the record shouldn´t be change!
                 */
                $newRecord = $controller->update($record);
                $oldRecord->seq = $newRecord->seq;
                $controller->update($oldRecord);
            }
        }
    }

    /**
     * generate notes values
     * @return |null
     * @throws Tinebase_Exception
     */
    protected function _getNotes()
    {
        if (!extension_loaded('yaml')) {
            throw new Tinebase_Exception('yaml extension required');
        }

        $importDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'Addressbook' . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR .  'import' . DIRECTORY_SEPARATOR . 'Notes';

        //@todo give notes name as variable
        $path = $importDir . DIRECTORY_SEPARATOR . 'notes.yml';
        if (file_exists($path)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' import notes from file: ' . $path);
            $setData = yaml_parse_file($path);
        }

        if (!isset($setData)) {
            return null;
        }

        $notes = [];

        foreach ($setData['notes'] as $note){
            $splitNote = explode(' => ', $note);
            $notes[$splitNote[0]] = $splitNote[1];
        }

        $this->_additionalOptions['notes'] = $notes;
    }
}
