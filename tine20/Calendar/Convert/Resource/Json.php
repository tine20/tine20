<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Resource_Json extends Tinebase_Convert_Json
{
   /**
    * converts Tinebase_Record_Abstract to external format
    *
    * @param  Tinebase_Record_Abstract $_record
    * @return mixed
    */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        $jsonData = parent::fromTine20Model($_record);
        
        if (Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES)) {
            $jsonData['grants'] = Tinebase_Container::getInstance()->getGrantsOfContainer($_record->container_id, TRUE)->toArray();
            $jsonData['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($jsonData['grants']);
        }
        
        return $jsonData;
    }
}
