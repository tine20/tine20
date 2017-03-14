<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * node grants controller
 * 
 * @package     Tinebase
 * @subpackage  FileSystem
 * 
 */
class Tinebase_Tree_NodeGrants extends Tinebase_Controller_Record_Grants
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by doRightChecks
     * 
     * @var boolean
     */
    protected $_doRightChecks = FALSE;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * omit mod log for this records
     * 
     * @var boolean
     */
    protected $_omitModLog = TRUE;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_grantsModel = 'Tinebase_Model_Grants';

    /**
     * @var string acl record property for join with acl table
     */
    protected $_aclIdProperty = 'acl_node';

    /**
     * @var Tinebase_Tree_NodeGrants
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Tree_Node();
        $this->_grantsBackend = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => $this->_grantsModel,
            'tableName' => 'tree_node_acl'
        ));
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
     * @return Tinebase_Tree_NodeGrants
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Tree_NodeGrants();
        }
        
        return self::$_instance;
    }
}
