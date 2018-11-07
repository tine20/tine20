<?php
/**
 * convert functions for Container from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for Container from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Container_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     * 
     * @param  Tinebase_Record_Interface $_model
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_model)
    {
        $recordSet = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($_model));
        $result = $this->fromTine20RecordSet($recordSet);

        
        return $result[0];
    }


    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        $response = array();
        foreach ($_records as $container) {
            $container->xprops();
            $containerArray = $container->toArray();

            if ($container instanceof Tinebase_Model_Container) {
                $containerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container)->toArray();
                $containerArray['path'] = $container->getPath();
                $ownerId = $container->getOwner();
            } else {
                $containerArray['path'] = "personal/{$container->getId()}";
                $ownerId = $container->getId();
            }
            if (! empty($ownerId)) {
                try {
                    $containerArray['ownerContact'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($ownerId, true)->toArray();
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " can't resolve ownerContact: " . $e);
                }
            }

            $response[] = $containerArray;
        }

        return $response;
    }
}
