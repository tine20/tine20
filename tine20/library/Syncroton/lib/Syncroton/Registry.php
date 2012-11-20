<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Syncroton
 * @package    Syncroton_Registry
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Registry.php 10020 2009-08-18 14:34:09Z j.fischer@metaways.de $
 */

/**
 * Generic storage class helps to manage global data.
 *
 * @category   Syncroton
 * @package    Syncroton_Registry
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Syncroton_Registry extends ArrayObject
{
    const CALENDAR_DATA_CLASS = 'calendar_data_class';
    const CONTACTS_DATA_CLASS = 'contacts_data_class';
    const EMAIL_DATA_CLASS    = 'email_data_class';
    const TASKS_DATA_CLASS    = 'tasks_data_class';
    const GAL_DATA_CLASS      = 'gal_data_class';
    
    const DEFAULT_POLICY      = 'default_policy';
    const PING_TIMEOUT        = 'ping_timeout';
    const QUIET_TIME          = 'quiet_time';
    
    const DATABASE            = 'database';
    const TRANSACTIONMANAGER  = 'transactionmanager';
    
    const CONTENTSTATEBACKEND = 'contentstatebackend';
    const DEVICEBACKEND       = 'devicebackend';
    const FOLDERBACKEND       = 'folderbackend';
    const POLICYBACKEND       = 'policybackend';
    const SYNCSTATEBACKEND    = 'syncstatebackend';
    
    /**
     * Class name of the singleton registry object.
     * @var string
     */
    private static $_registryClassName = 'Syncroton_Registry';

    /**
     * Registry object provides storage for shared objects.
     * @var Syncroton_Registry
     */
    private static $_registry = null;

    /**
     * Retrieves the default registry instance.
     *
     * @return Syncroton_Registry
     */
    public static function getInstance()
    {
        if (self::$_registry === null) {
            self::init();
        }

        return self::$_registry;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDatabase()
    {
        return self::get(self::DATABASE);
    }
    
    /**
     * return transaction manager class 
     * 
     * @return Syncroton_TransactionManagerInterface
     */
    public static function getTransactionManager()
    {
        if (!self::isRegistered(self::TRANSACTIONMANAGER)) {
            self::set(self::TRANSACTIONMANAGER, Syncroton_TransactionManager::getInstance());
        }
        
        return self::get(self::TRANSACTIONMANAGER);
    } 
    
    /**
     * Set the default registry instance to a specified instance.
     *
     * @param Syncroton_Registry $registry An object instance of type Syncroton_Registry,
     *   or a subclass.
     * @return void
     * @throws Zend_Exception if registry is already initialized.
     */
    public static function setInstance(Syncroton_Registry $registry)
    {
        if (self::$_registry !== null) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Registry is already initialized');
        }

        self::setClassName(get_class($registry));
        self::$_registry = $registry;
    }

    /**
     * Initialize the default registry instance.
     *
     * @return void
     */
    protected static function init()
    {
        self::setInstance(new self::$_registryClassName());
    }

    /**
     * Set the class name to use for the default registry instance.
     * Does not affect the currently initialized instance, it only applies
     * for the next time you instantiate.
     *
     * @param string $registryClassName
     * @return void
     * @throws Zend_Exception if the registry is initialized or if the
     *   class name is not valid.
     */
    public static function setClassName($registryClassName = 'Syncroton_Registry')
    {
        if (self::$_registry !== null) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Registry is already initialized');
        }

        if (!is_string($registryClassName)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception("Argument is not a class name");
        }

        /**
         * @see Zend_Loader
         */
        if (!class_exists($registryClassName)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($registryClassName);
        }

        self::$_registryClassName = $registryClassName;
    }

    /**
     * Unset the default registry instance.
     * Primarily used in tearDown() in unit tests.
     * @returns void
     */
    public static function _unsetInstance()
    {
        self::$_registry = null;
    }

    /**
     * getter method, basically same as offsetGet().
     *
     * This method can be called from an object of type Syncroton_Registry, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index - get the value associated with $index
     * @return mixed
     * @throws Zend_Exception if no entry is registerd for $index.
     */
    public static function get($index)
    {
        $instance = self::getInstance();

        if (!$instance->offsetExists($index)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception("No entry is registered for key '$index'");
        }

        return $instance->offsetGet($index);
    }
    
    /**
     * returns content state backend
     * 
     * creates Syncroton_Backend_Content on the fly if not before via
     * Syncroton_Registry::set(self::CONTENTSTATEBACKEND, $backend);
     * 
     * @return Syncroton_Backend_IContent
     */
    public static function getContentStateBackend()
    {
        if (!self::isRegistered(self::CONTENTSTATEBACKEND)) {
            self::set(self::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(self::getDatabase()));
        }
        
        return self::get(self::CONTENTSTATEBACKEND);
    }

    /**
     * returns device backend
     * 
     * creates Syncroton_Backend_Device on the fly if not before via
     * Syncroton_Registry::set(self::DEVICEBACKEND, $backend);
     * 
     * @return Syncroton_Backend_IDevice
     */
    public static function getDeviceBackend()
    {
        if (!self::isRegistered(self::DEVICEBACKEND)) {
            self::set(self::DEVICEBACKEND, new Syncroton_Backend_Device(self::getDatabase()));
        }
        
        return self::get(self::DEVICEBACKEND);
    }

    /**
     * returns folder backend
     * 
     * creates Syncroton_Backend_Folder on the fly if not before via
     * Syncroton_Registry::set(self::FOLDERBACKEND, $backend);
     * 
     * @return Syncroton_Backend_IFolder
     */
    public static function getFolderBackend()
    {
        if (!self::isRegistered(self::FOLDERBACKEND)) {
            self::set(self::FOLDERBACKEND, new Syncroton_Backend_Folder(self::getDatabase()));
        }
        
        return self::get(self::FOLDERBACKEND);
    }

    /**
     * return ping timeout 
     * 
     * sleep "ping timeout" seconds between folder checks in Ping and Sync command 
     * 
     * @return int
     */
    public static function getPingTimeout()
    {
        if (!self::isRegistered(self::PING_TIMEOUT)) {
            return 60;
        }
        
        return self::get(self::PING_TIMEOUT);
    }
    
    /**
     * returns policy backend
     * 
     * creates Syncroton_Backend_Policy on the fly if not set before via
     * Syncroton_Registry::set(self::POLICYBACKEND, $backend);
     * 
     * @return Syncroton_Backend_ISyncState
     */
    public static function getPolicyBackend()
    {
        if (!self::isRegistered(self::POLICYBACKEND)) {
            self::set(self::POLICYBACKEND, new Syncroton_Backend_Policy(self::getDatabase()));
        }
        
        return self::get(self::POLICYBACKEND);
    }

    /**
     * return quiet time 
     * 
     * don't check folders if last sync was "quiet time" seconds ago 
     * 
     * @return int
     */
    public static function getQuietTime()
    {
        if (!self::isRegistered(self::QUIET_TIME)) {
            return 180;
        }
        
        return self::get(self::QUIET_TIME);
    }
    
    /**
     * returns syncstate backend
     * 
     * creates Syncroton_Backend_SyncState on the fly if not before via
     * Syncroton_Registry::set(self::SYNCSTATEBACKEND, $backend);
     * 
     * @return Syncroton_Backend_ISyncState
     */
    public static function getSyncStateBackend()
    {
        if (!self::isRegistered(self::SYNCSTATEBACKEND)) {
            self::set(self::SYNCSTATEBACKEND, new Syncroton_Backend_SyncState(self::getDatabase()));
        }
        
        return self::get(self::SYNCSTATEBACKEND);
    }

    /**
     * setter method, basically same as offsetSet().
     *
     * This method can be called from an object of type Syncroton_Registry, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index The location in the ArrayObject in which to store
     *   the value.
     * @param mixed $value The object to store in the ArrayObject.
     * @return void
     */
    public static function set($index, $value)
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    public static function setDatabase(Zend_Db_Adapter_Abstract $db)
    {
        self::set(self::DATABASE, $db);
    }
    
    public static function setCalendarDataClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }
    
        self::set(self::CALENDAR_DATA_CLASS, $className);
    }
    
    public static function setContactsDataClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }
    
        self::set(self::CONTACTS_DATA_CLASS, $className);
    }
    
    public static function setEmailDataClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }
    
        self::set(self::EMAIL_DATA_CLASS, $className);
    }
    
    public static function setTasksDataClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }
    
        self::set(self::TASKS_DATA_CLASS, $className);
    }

    public static function setGALDataClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }

        self::set(self::GAL_DATA_CLASS, $className);
    }

    public static function setTransactionManager($manager)
    {
        self::set(self::TRANSACTIONMANAGER, $manager);
    }
    
    /**
     * Returns TRUE if the $index is a named value in the registry,
     * or FALSE if $index was not found in the registry.
     *
     * @param  string $index
     * @return boolean
     */
    public static function isRegistered($index)
    {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->offsetExists($index);
    }

    /**
     * Constructs a parent ArrayObject with default
     * ARRAY_AS_PROPS to allow acces as an object
     *
     * @param array $array data array
     * @param integer $flags ArrayObject flags
     */
    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    /**
     * @param string $index
     * @returns mixed
     *
     * Workaround for http://bugs.php.net/bug.php?id=40442 (ZF-960).
     */
    public function offsetExists($index)
    {
        return array_key_exists($index, $this);
    }

}
