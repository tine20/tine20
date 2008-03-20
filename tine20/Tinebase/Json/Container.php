<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Json Container class
 * 
 * @package     Tinebase
 * @subpackage  Container
 */
class Tinebase_Json_Container
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
        $application = Tinebase_Controller::getApplicationInstance($application);
        
        switch($containerType) {
            case Tinebase_Container::TYPE_PERSONAL:
                $container = $application->getPersonalContainer(Zend_Registry::get('currentAccount'), $owner, Tinebase_Container::GRANT_READ);
                break;
            case Tinebase_Container::TYPE_SHARED:
                $container = $application->getSharedContainer(Zend_Registry::get('currentAccount'), Tinebase_Container::GRANT_READ);
                break;
            case 'otherUsers':
                $container = $application->getOtherUsers(Zend_Registry::get('currentAccount'), Tinebase_Container::GRANT_READ);
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
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        switch($containerType) {
            case Tinebase_Container::TYPE_SHARED:
                $container = Tinebase_Container::getInstance()->addSharedContainer($accountId, $application, $containerName);
                break;
            case Tinebase_Container::TYPE_PERSONAL:
                $container = Tinebase_Container::getInstance()->addPersonalContainer($accountId, $application, $containerName);
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
        Tinebase_Container::getInstance()->deleteContainer($containerId);
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
        $container = Tinebase_Container::getInstance()->renameContainer($containerId, $newName);
        return $container->toArray();
    }
    
    /**
     * returns container grants
     * 
     * @param int $containerId
     * @return array
     * @throws Exception
     */
    public function getContainerGrants($containerId) 
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Tinebase_Container::getInstance()->getAllGrants($containerId)->toArray();
        $result['totalcount'] = count($result['results']);
        
        foreach($result['results'] as &$value) {
            if($value['accountId'] === NULL) {
                $value['accountName'] = array('accountDisplayName' => 'Anyone');
            } else {
                $value["accountName"] = Tinebase_Account::getInstance()->getAccountById($value['accountId'])->toArray();
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
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', Zend_Json::decode($grants));
        
        Tinebase_Container::getInstance()->setAllGrants($containerId, $newGrants);
               
        return $this->getContainerGrants($containerId);
    }
    
}