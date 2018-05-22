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

        // no ACL check here! we already have the resource, so get the container
        $jsonData['container_id'] = Tinebase_Container::getInstance()->getContainerById($_record->container_id)
            ->toArray();

        $user = Tinebase_Core::getUser();
        if ($user->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES) ||
                Tinebase_Container::getInstance()->hasGrant($user->getId(), $_record->container_id,
                    Calendar_Model_ResourceGrants::RESOURCE_READ)) {
            $jsonData['grants'] = Tinebase_Container::getInstance()->getGrantsOfContainer($_record->container_id, true)
                ->toArray();
            $jsonData['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($jsonData['grants']);
        }
        
        return $jsonData;
    }
}
