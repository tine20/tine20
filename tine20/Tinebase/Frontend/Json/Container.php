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
class Tinebase_Frontend_Json_Container
{
   /**
     * gets container / container folder
     * 
     * Backend function for containerTree widget
     * 
     * @todo move getOtherUsers to own function
     * 
     * @param  string $application
     * @param  string $containerType
     * @param  string $owner
     * @return array
     */
    public function getContainer($application, $containerType, $owner)
    {       
        switch($containerType) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $application, $owner, Tinebase_Model_Grants::GRANT_READ);
                break;
                
            case Tinebase_Model_Container::TYPE_SHARED:
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $application, Tinebase_Model_Grants::GRANT_READ);
                break;
                
            case 'otherUsers':
                $containers = Tinebase_Container::getInstance()->getOtherUsers(Tinebase_Core::getUser(), $application, Tinebase_Model_Grants::GRANT_READ);
                break;
                
            default:
                throw new Exception('no such NodeType');
        }
        
        $response = array();
        foreach ($containers as $container) {
            $containerArray = $container->toArray();
            
            if ($container instanceof Tinebase_Model_Container) {
                $containerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container->getId())->toArray();
                $containerArray['path'] = Tinebase_Container::getInstance()->getPath($container);
            } else {
                $containerArray['path'] = "personal/{$container->getId()}";
            }
            
            $response[] = $containerArray;
        }
        
        return $response;
    }
    
    /**
     * adds a container
     * 
     * @param   string $application
     * @param   string $containerName
     * @param   string $containerType
     * @return  array new container
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function addContainer($application, $name, $containerType)
    {
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $name,
            'type'              => $containerType,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($application)->getId() 
        ));
        
        if($newContainer->type !== Tinebase_Model_Container::TYPE_PERSONAL and $newContainer->type !== Tinebase_Model_Container::TYPE_SHARED) {
            throw new Tinebase_Exception_InvalidArgument('Can add personal or shared containers only');
        }
                
        $container = Tinebase_Container::getInstance()->addContainer($newContainer);
        
        $result = $container->toArray();
        $result['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container->getId())->toArray();
        $result['path'] = Tinebase_Container::getInstance()->getPath($container);
        return $result;
    }
    
    /**
     * deletes a container
     * 
     * @param   int     $containerId
     * @return  string  success
     */
    public function deleteContainer($containerId)
    {
        Tinebase_Container::getInstance()->deleteContainer($containerId);
        
        return array(
            'success'      => TRUE
        );
    }
    
    /**
     * renames a container
     * 
     * @param  int      $containerId
     * @param  string   $newName
     * @return array    updated container
     * @throws Tinebase_Exception
     */
    public function renameContainer($containerId, $newName)
    {
        try {
            $container = Tinebase_Container::getInstance()->setContainerName($containerId, $newName);
        } catch (Tinebase_Exception $e) {
            throw new Tinebase_Exception('Container not found or permission to set containername denied!');
        }
        
        $result = $container->toArray();
        $result['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container->getId())->toArray();
        $result['path'] = Tinebase_Container::getInstance()->getPath($container);
        return $result;
    }
    
    /**
     * returns container grants
     * 
     * @param   int     $containerId
     * @return  array
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getContainerGrants($containerId) 
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId)->toArray();
        $result['totalcount'] = count($result['results']);
        
        foreach($result['results'] as &$value) {
            switch($value['account_type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                	try {
                		$account = Tinebase_User::getInstance()->getUserById($value['account_id']);
                	}
		            catch (Tinebase_Exception_NotFound $e) {
		                $account = Tinebase_User::getInstance()->getNonExistentUser();
		            }
                    $value['account_name'] = $account->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    $value['account_name'] = Tinebase_Group::getInstance()->getGroupById($value['account_id'])->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $value['account_name'] = array('accountDisplayName' => 'Anyone');
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                    break;    
            }            
        }
        
        return $result;
    }
    
    /**
     * sets new grants for given container
     * 
     * @param  int      $containerId
     * @param  array    $grants
     * @return array    see getContainerGrants
     */
    public function setContainerGrants($containerId, $grants)
    {
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $grants);
        
        $grants = Tinebase_Container::getInstance()->setGrants($containerId, $newGrants);
               
        return $this->getContainerGrants($containerId);
    }
    
    /**
     * move records to container
     * 
     * @param string $targetContainerId
     * @param array  $recordIds
     * @param string $applicationName
     * @param string $model
     * @return array
     */
    public function moveRecordsToContainer($targetContainerId, $recordIds, $applicationName, $model)
    {
        Tinebase_Container::getInstance()->moveRecordsToContainer($targetContainerId, $recordIds, $applicationName, $model);
        
        return array(
            'status'    => 'success'
        );
    }
}
