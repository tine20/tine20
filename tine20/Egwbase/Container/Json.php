<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

class Egwbase_Container_Json
{
   /**
     * gets container / container folder
     * 
     * Backend function for containerTree widget
     * 
     * @param string $application
     * @param string $containerType
     * @param string $owner
     * @return string JSON
     */
    public function getContainer($application, $containerType, $owner =  NULL)
    {       
        switch($containerType) {
            case Egwbase_Container_Container::TYPE_PERSONAL:
                $container = Egwbase_Container_Container::getInstance()->getPersonalContainer($application,$owner);
                break;
            case Egwbase_Container_Container::TYPE_SHARED:
                $container = Egwbase_Container_Container::getInstance()->getSharedContainer($application);
                break;
            case 'OtherUsers':
                $container = Egwbase_Container_Container::getInstance()->getOtherUsers($application);
                break;
            default:
                throw new Exception('no such NodeType');
        }
        echo Zend_Json::encode($container->toArray());

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }
    
    /**
     * adds a container
     * 
     * @param string $application
     * @param string $containerName
     * $param string $containerType
     * @return array new container
     */
    public function addContainer($application, $containerName, $containerType)
    {
        switch($containerType) {
            case Egwbase_Container_Container::TYPE_SHARED:
                $container = Egwbase_Container_Container::getInstance()->addSharedContainer($application, $containerName);
                break;
            case Egwbase_Container_Container::TYPE_PERSONAL:
                $container = Egwbase_Container_Container::getInstance()->addPersonalContainer($application, $containerName);
                break;
            default:
                throw new Exception('no such containerType');
        }
        return $container->toArray();
    }
    
    /**
     * deletes a container
     * 
     * @param int $containerId
     * @return string success
     * @throws Exception
     */
    public function deleteContainer($containerId)
    {
        Egwbase_Container_Container::getInstance()->deleteContainer($containerId);
        return 'success';
    }
    
    /**
     * renames a container
     * 
     * @param int $containerId
     * $param string $newName
     * @return array updated container
     */
    public function renameContainer($containerId, $newName)
    {
        $container = Egwbase_Container_Container::getInstance()->renameContainer($containerId, $newName);
        return $container->toArray();
    }
    
    /**
     * returns container grants
     * 
     * @param int $containerId
     * @return array
     * @throws Exception
     */
    public function getContainerGrants($containerId) {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Egwbase_Container_Container::getInstance()->getAllGrants($containerId)->toArray();
        $result['totalcount'] = count($result['results']);
        
        foreach($result['results'] as &$value) {
            if($value['accountId'] === NULL) {
                $value['accountName'] = array('accountDisplayName' => 'Anyone');
            } else {
                $value["accountName"] = Egwbase_Account::getInstance()->getAccountById($value['accountId'])->toArray();
            }
        }
        
        return $result;
    }
    
    /**
     * sets new grants for given container
     * 
     * @param int $containerId
     * @param array $grants
     * @return array, see getContainerGrants
     */
    public function setContainerGrants($containerId, $grants)
    {
        $newGrants = new Egwbase_Record_RecordSet(Zend_Json::decode($grants), 'Egwbase_Record_Grants');
        
        Egwbase_Container_Container::getInstance()->setAllGrants($containerId, $newGrants);
               
        return $this->getContainerGrants($containerId);
    }
    
}