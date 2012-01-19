<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Json implements Tinebase_Convert_Interface
{
    /**
     * converts external format to Tinebase_Record_Abstract
     * 
     * @param  mixed                     $_blob   the input data to parse
     * @param  Tinebase_Record_Abstract  $_record  update existing record
     * @return Tinebase_Record_Abstract
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null)
    {
        throw new Tinebase_Exception_NotImplemented('From json to record is not implemented yet');
    }
    
    /**
     * converts Tinebase_Record_Abstract to external format
     * 
     * @param  Tinebase_Record_Abstract $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (! $_record) {
            return array();
        }
        
        $_record->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_record->bypassFilters = true;
        self::resolveContainer($_record);
        
        return $_record->toArray();
    }
    
    /**
     * resolve container id to container record
     * 
     * @param Tinebase_Record_Abstract $_record
     */
    public static function resolveContainer($_record)
    {
        if (! $_record->has('container_id') || empty($_record->container_id)) {
            return;
        }
        
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return;
        }
        
        // @todo check if we can remove the toArray() here as this should be handled by the container toArray fn
        $container->account_grants = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $_record->container_id)->toArray();
        
        $container->path = $container->getPath();
        
        $_record->container_id = $container;
    }

    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param  Tinebase_Record_RecordSet  $_records
     * @param  array $_resolveUserFields
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records, $_resolveUserFields = array())
    {
        if (count($_records) == 0) {
            return array();
        }
        
        Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($_records, $_resolveUserFields);
        
        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_records->convertDates = true;
        
        $result = $_records->toArray();
        
        return $result;
    }
}
