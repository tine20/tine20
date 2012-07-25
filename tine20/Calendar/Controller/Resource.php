<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Resources Controller
 * 
 * @package Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_Resource extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var boolean
     * 
     * just set is_delete=1 if record is going to be deleted
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * @var Calendar_Controller_Resource
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Resource';
        
        $this->_backend         = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'cal_resources'
        ));
        $this->_backend->setModlogActive(TRUE);
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_Resource
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_Resource();
        }
        return self::$_instance;
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // create a calendar for this resource
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => $_record->name,
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => $this->_backend->getType(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'model'             => 'Calendar_Model_Event'
        )), NULL, TRUE);
        
        // remove default admin
        $grants = Tinebase_Container::getInstance()->setGrants($container->getId(), new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'      => '0',
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Model_Grants::GRANT_FREEBUSY  => true
            )
        )), TRUE, FALSE);
        
        $_record->container_id = $container->getId();
        return parent::create($_record);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
        $container->name = $_record->name;
        Tinebase_Container::getInstance()->update($container);
        
        return parent::update($_record);
    }
    
    /**
     * check if user has the right to manage resources
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage resources");
                }
                break;
            default;
               break;
        }
    }
}
