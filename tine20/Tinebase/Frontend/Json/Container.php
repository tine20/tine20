<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Json Container class
 * 
 * @package     Tinebase
 * @subpackage  Container
 */
class Tinebase_Frontend_Json_Container extends  Tinebase_Frontend_Json_Abstract
{
    /**
     * Search for Container matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchContainers($filter, $paging)
    {
        return parent::_search($filter, $paging, Tinebase_Container::getInstance(), 'Tinebase_Model_ContainerFilter');
    }

    /**
     * gets container / container folder
     * 
     * Backend function for containerTree widget
     * 
     * @todo move getOtherUsers to own function
     * 
     * @param  string $model
     * @param  string $containerType
     * @param  string $owner
     * @param  array $requiredGrants
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getContainer($model, $containerType, $owner, $requiredGrants = NULL)
    {
        if (!$requiredGrants) {
            $requiredGrants = Tinebase_Model_Grants::GRANT_READ;
        }
        switch ($containerType) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $model, $owner, $requiredGrants);
                $containers->sort('name');
                break;
                
            case Tinebase_Model_Container::TYPE_SHARED:
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $model, $requiredGrants);
                break;
                
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                $containers = Tinebase_Container::getInstance()->getOtherUsers(Tinebase_Core::getUser(), $model, $requiredGrants);
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument('no such NodeType');
        }
        
        $converter = new Tinebase_Convert_Container_Json();
        return $converter->fromTine20RecordSet($containers);
    }
    
    /**
     * adds a container
     * 
     * @param   string $application
     * @param   string $containerName
     * @param   string $containerType
     * @param   string $modelName
     * @return  array new container
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function addContainer($application, $name, $containerType, $modelName)
    {
        $modelName = strstr($modelName, '_Model_') ? $modelName : $application . '_Model_' . $modelName;
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $name,
            'type'              => $containerType,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($application)->getId(),
            'model'             => $modelName
        ));
        
        if($newContainer->type !== Tinebase_Model_Container::TYPE_PERSONAL and $newContainer->type !== Tinebase_Model_Container::TYPE_SHARED) {
            throw new Tinebase_Exception_InvalidArgument('Can add personal or shared containers only');
        }
                
        $container = Tinebase_Container::getInstance()->addContainer($newContainer);
        
        $result = $container->toArray();
        $result['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container)->toArray();
        $result['path'] = $container->getPath();
        return $result;
    }
    
    /**
     * deletes a container
     * 
     * @param   int     $containerId
     * @return  array
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
     */
    public function renameContainer($containerId, $newName)
    {
        $container = Tinebase_Container::getInstance()->setContainerName($containerId, $newName);
        
        $result = $container->toArray();
        $result['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container)->toArray();
        $result['path'] = $container->getPath();
        return $result;
    }
    
    /**
     * sets color of a container
     * 
     * @param  int      $containerId
     * @param  string   $color
     * @return array    updated container
     * @throws Tinebase_Exception
     */
    public function setContainerColor($containerId, $color)
    {
        try {
            $container = Tinebase_Container::getInstance()->setContainerColor($containerId, $color);
        } catch (Tinebase_Exception $e) {
            throw new Tinebase_Exception('Container not found or permission to set containername denied!');
        }
        
        $result = $container->toArray();
        $result['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(),
            $container)->toArray();
        $result['path'] = $container->getPath();
        return $result;
    }
    
    /**
     * returns container grants
     * 
     * @param   int     $containerId
     * @return  array
     */
    public function getContainerGrants($containerId) 
    {
        $container = Tinebase_Container::getInstance()->getContainerById($containerId);

        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId)->toArray();
        $result['totalcount'] = count($result['results']);
        $result['results'] = self::resolveAccounts($result['results']);
        
        return $result;
    }
    
    /**
     * resolve accounts in grants
     * 
     * @param array $_grants
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     * 
     * @todo think about resolving before converting to array
     * TODO move to Tinebase_Model_Grants
     */
    public static function resolveAccounts($_grants)
    {
        if (! is_array($_grants)) {
            return [];
        }

        foreach ($_grants as &$value) {
            switch ($value['account_type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    try {
                        $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $value['account_id']);
                    } catch (Tinebase_Exception_NotFound $e) {
                        $account = Tinebase_User::getInstance()->getNonExistentUser();
                    }
                    $value['account_name'] = $account->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    try {
                        $group = Tinebase_Group::getInstance()->getGroupById($value['account_id']);
                    } catch (Tinebase_Exception_Record_NotDefined $e) {
                        $group = Tinebase_Group::getInstance()->getNonExistentGroup();
                    }
                    $value['account_name'] = $group->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                    try {
                        $role = Tinebase_Acl_Roles::getInstance()->getRoleById($value['account_id']);
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        $role = Tinebase_Acl_Roles::getInstance()->getNonExistentRole();
                    }
                    $value['account_name'] = $role->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $value['account_name'] = array('accountDisplayName' => Tinebase_Translation::getTranslation('Tinebase')->_('Anyone'));
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                    break;
            }
        }
        
        return $_grants;
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
        $grants = ($grants) ? $grants : array();

        $container = Tinebase_Container::getInstance()->getContainerById($containerId);

        $newGrants = new Tinebase_Record_RecordSet($container->getGrantClass(), $grants);
        
        Tinebase_Container::getInstance()->setGrants($containerId, $newGrants);
               
        return $this->getContainerGrants($containerId);
    }
    
    /**
     * move records to container
     * 
     * @param string $targetContainerId
     * @param array  $filterData
     * @param string $applicationName
     * @param string $model
     * @return array
     */
    public function moveRecordsToContainer($targetContainerId, $filterData, $applicationName, $model)
    {
        $filterModel = $applicationName . '_Model_' . $model . 'Filter';

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterModel);
        $filter->setFromArrayInUsersTimezone($filterData);

        $this->_longRunningRequest();
        
        $recordController = Tinebase_Core::getApplicationInstance($applicationName, $model);
        $result = $recordController->move($filter, $targetContainerId);
        
        return array(
            'status'    => 'success',
            'results'   => $result,
        );
    }
}
