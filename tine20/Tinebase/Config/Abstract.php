<?php
abstract class Tinebase_Config_Abstract
{
        
//    /**
//     * entry is readable part of registry
//     * @var int
//     */
//    const SCOPE_REGISTRY = 1;
//    /**
//     * entry is get- setable in admin module
//     * @var int
//     */
//    const SCOPE_ADMIN = 2;
//    /**
//     * entry is get- setable in setup
//     * @var int
//     */
//    const SCOPE_SETUP = 4;
    
    /**
     * application name this config belongs to
     *
     * @var string
     */
    protected $_appName;
    
    /**
     * config file data.
     * 
     * @var array
     */
    private static $_configFileData = NULL;
    
    /**
     * config database backend
     * 
     * @var Tinebase_Backend_Sql
     */
    private static $_backend;
    
    
//    /**
//     * Array of property definition arrays
//     * 
//     * @staticvar array
//     */
//    abstract protected static $_properties = array();
//    
//    /**
//     * get properties definitions 
//     * 
//     * NOTE: as static late binding is not possible in PHP < 5.3 
//     *       this function has to be implemented in each subclass
//     * 
//     * @return array
//     */
//    abstract public static function getProperties();
    
//    /**
//     * the constructor
//     *
//     * don't use the constructor. use the singleton 
//     */    
//    protected function __construct() 
//    {
//        $this->_configFileData = $this->_getConfigFileData();
//    }
    
    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($_name)
    {
        return $this->get($_name);
    }
    
    /**
     * returns data from central config.inc.php file
     * 
     * @return array
     */
    protected function _getConfigFileData()
    {
        if (! self::$_configFileData) {
            $configData = include('config.inc.php');
            
            if($configData === false) {
                die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
            }
            
            self::$_configFileData = $configData;
        }
        
        return self::$_configFileData;
    }
    
    /**
     * returns config database backend
     * 
     * @return Tinebase_Backend_Sql
     */
    protected function _getBackend()
    {
        if (! self::$_backend) {
            self::$_backend = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_Config', 
                'tableName' => 'config',
            ));
        }
        
        return self::$_backend;
    }
}