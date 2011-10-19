<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 * 
 * @todo add implements when Tinebase_Convert_Interface is available
 * @todo add factory for converters?
 */
class Tinebase_Convert_Json // implements Tinebase_Convert_Interface
{
    /**
     * converts external format to Tinebase_Record_Abstract
     * 
     * @param  mixed                     $_blob   the input data to parse
     * @param  Tinebase_Record_Abstract  $_model  update existing record
     * @return Tinebase_Record_Abstract
     * 
     * @todo rename model to record?
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null)
    {
        throw new Tinebase_Exception_NotImplemented('From json to record is not implemented yet');
    }
    
    /**
     * converts Tinebase_Record_Abstract to external format
     * 
     * @param  Tinebase_Record_Abstract $_record
     * @return mixed
     * 
     * @todo rename model to record?
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_model)
    {
        if (! $_model) {
            return array();
        }
        
        $_model->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_model->bypassFilters = true;
        
        $result = $_model->toArray();
        
        if ($_model->has('container_id')) {
            $container = Tinebase_Container::getInstance()->getContainerById($_model->container_id);
        
            $result['container_id'] = $container->toArray();
            $result['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $_model->container_id)->toArray();
            $result['container_id']['path'] = $container->getPath();
        }
        
        return $result;
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
        
        if ($_records->getFirstRecord()->has('container_id')) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        }
        
        if ($_records->getFirstRecord()->has('tags')) {
            Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        }
        
        if (array_key_exists($_records->getRecordClassName(), $_resolveUserFields)) {
            Tinebase_User::getInstance()->resolveMultipleUsers($_records, $_resolveUserFields[$_records->getRecordClassName()], TRUE);
        }
        
        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_records->convertDates = true;
        
        $result = $_records->toArray();
        
        return $result;
    }
}
