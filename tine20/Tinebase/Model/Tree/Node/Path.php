<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class representing one node path
 * 
 * @package     Tinebase
 * @subpackage  Model
 * 
 * @property    string                      containerType
 * @property    string                      containerOwner
 * @property    string                      flatpath           "real" name path like /personal/user/containername
 * @property    string                      statpath           id path like /personal/USERID/nodeName1/nodeName2/...
 * @property    string                      realpath           path without app/type/container stuff 
 * @property    string                      streamwrapperpath
 * @property    Tinebase_Model_Application  application
 * @property    Tinebase_Model_FullUser     user
 * @property    string                      name (last part of path)
 * @property    Tinebase_Model_Tree_Node_Path parentrecord
 * 
 * @todo rename this to Tinebase_Model_Tree_Node_FoldersPath ?
 * 
 * exploded flat path should look like this:
 * 
 * [0] => app id [required]
 * [1] => folders [required]
 * [2] => type [required] (personal|shared)
 * [3] => container name | accountLoginName
 * [4] => container name | directory
 * [5] => directory
 * [6] => directory
 * [...]
 */
class Tinebase_Model_Tree_Node_Path extends Tinebase_Record_Abstract
{
    /**
     * streamwrapper path prefix
     */
    const STREAMWRAPPERPREFIX = 'tine20://';
    
    /**
     * root type
     */
    const TYPE_ROOT = 'root';

    /**
     * folders path part
     */
    const FOLDERS_PART = 'folders';

    /**
     * records path part
     */
    const RECORDS_PART = 'records';

    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'flatpath';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array (
        'containerType'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'containerOwner'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'flatpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'statpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'realpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'streamwrapperpath' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'parentrecord'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract::__toString()
     */
    public function __toString()
    {
        return $this->flatpath;
    }
    
    /**
     * create new path record from given (flat) path string like this:
     *  /c09439cb1d73e923b31affdecb8f2c8feff90d66/folders/personal/f11458741d0319755a7366c1d782172ecbf1305f
     * 
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node_Path
     */
    public static function createFromPath($_path)
    {
        $pathRecord = ($_path instanceof Tinebase_Model_Tree_Node_Path) ? $_path : new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'  => $_path
        ));
        
        return $pathRecord;
    }

    /**
     * create new path record from given realpath string like this:
     *  /personal/tine20admin/somedir
     *
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node_Path
     */
    public static function createFromRealPath($_path, $_application, $part = self::FOLDERS_PART)
    {
        $flatpath = '/' . $_application->name . '/' . $part . $_path;
        return new Tinebase_Model_Tree_Node_Path(array(
            'application' => $_application,
            'flatpath'  => $flatpath
        ));
    }

    /**
     * create new path record from given stat path (= path with ids) string
     *
     * @param string $statPath
     * @param string $appName
     * @return Tinebase_Model_Tree_Node_Path
     */
    public static function createFromStatPath($statPath, $appName = null)
    {
        $statPath = trim($statPath, '/');
        $pathParts = explode('/', $statPath);
        if ($appName !== null) {
            $app = Tinebase_Application::getInstance()->getApplicationByName($appName);
            array_unshift($pathParts, $app->getId(), self::FOLDERS_PART);
        }
        $newStatPath = '/' . implode('/', $pathParts);

        if (count($pathParts) > 3) {
            if ($pathParts[2] === Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
                $containerType = Tinebase_FileSystem::FOLDER_TYPE_RECORDS;
            } else {
                // replace account id with login name
                try {
                    $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $pathParts[3], 'Tinebase_Model_FullUser');
                    $containerType = Tinebase_FileSystem::FOLDER_TYPE_PERSONAL;
                    $pathParts[3] = $user->accountLoginName;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // not a user -> shared
                    $containerType = Tinebase_FileSystem::FOLDER_TYPE_SHARED;
                }
            }
        } else if (count($pathParts) === 3) {
            $containerType = (in_array($pathParts[2], array(
                Tinebase_FileSystem::FOLDER_TYPE_SHARED,
                Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            ))) ? $pathParts[2] : self::TYPE_ROOT;
        } else {
            $containerType = self::TYPE_ROOT;
        }

        $flatPath = '/' . implode('/', $pathParts);
        $pathRecord = new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'      => $flatPath,
            'containerType' => $containerType,
            'statpath'      => $newStatPath,
        ));

        return $pathRecord;
    }

    /**
     * create new parent path record from given path string
     * 
     * @param string $_path
     * @return array with (Tinebase_Model_Tree_Node_Path, string)
     * 
     * @todo add child to model?
     */
    public static function getParentAndChild($_path)
    {
        $pathParts = $pathParts = explode('/', trim($_path, '/'));
        $child = array_pop($pathParts);
        
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath('/' . implode('/', $pathParts));
        
        return array(
            $pathRecord,
            $child
        );
    }
    
    /**
     * removes app id (and /folders namespace) from a path
     * 
     * @param string $_flatpath
     * @param Tinebase_Model_Application|string $_application
     * @return string
     */
    public static function removeAppIdFromPath($_flatpath, $_application)
    {
        $appId = (is_string($_application)) ? Tinebase_Application::getInstance()->getApplicationById($_application)->getId() : $_application->getId();
        return preg_replace('@^/' . $appId . '/' . self::FOLDERS_PART . '@', '', $_flatpath);
    }
    
    /**
     * get parent path of this record
     * 
     * @return Tinebase_Model_Tree_Node_Path
     */
    public function getParent()
    {
        if (! $this->parentrecord) {
            list($this->parentrecord, $unused) = self::getParentAndChild($this->flatpath);
        }
        return $this->parentrecord;
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * if flatpath is set, parse it and set the fields accordingly
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        
        if (isset($_data['flatpath'])) {
            $this->_parsePath($_data['flatpath']);
        }
    }
    
    /**
     * parse given path: check validity, set container type, do replacements
     * 
     * @param string $_path
     */
    protected function _parsePath($_path = NULL)
    {
        if ($_path === NULL) {
            $_path = $this->flatpath;
        }
        
        $pathParts = $this->_getPathParts($_path);
        
        $this->name                 = $pathParts[count($pathParts) - 1];
        $this->containerType        = isset($this->containerType) && in_array($this->containerType, array(
            Tinebase_FileSystem::FOLDER_TYPE_PERSONAL,
            Tinebase_FileSystem::FOLDER_TYPE_SHARED,
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS,
            Tinebase_FileSystem::FOLDER_TYPE_PREVIEWS,
        )) ? $this->containerType : $this->_getContainerType($pathParts);
        $this->containerOwner       = $this->_getContainerOwner($pathParts);
        $this->application          = $this->_getApplication($pathParts);
        $this->statpath             = isset($this->statpath) ? $this->statpath : $this->_getStatPath($pathParts);
        $this->realpath             = $this->_getRealPath($pathParts);
        $this->streamwrapperpath    = self::STREAMWRAPPERPREFIX . $this->statpath;
    }
    
    /**
     * get path parts
     * 
     * @param string $_path
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getPathParts($_path = NULL)
    {
        if ($_path === NULL) {
            $_path = $this->flatpath;
        }
        if (! is_string($_path)) {
            throw new Tinebase_Exception_InvalidArgument('Path needs to be a string!');
        }
        $pathParts = explode('/', trim($_path, '/'));
        if (count($pathParts) < 1) {
            throw new Tinebase_Exception_InvalidArgument('Invalid path: ' . $_path);
        }
        
        return $pathParts;
    }
    
    /**
     * get container type from path:
     *  - type is ROOT for all paths with 3 or less parts
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getContainerType($_pathParts)
    {
        $containerType = isset($_pathParts[2])? $_pathParts[2] : self::TYPE_ROOT;
        
        if (! in_array($containerType, array(
            Tinebase_FileSystem::FOLDER_TYPE_PERSONAL,
            Tinebase_FileSystem::FOLDER_TYPE_SHARED,
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS,
            Tinebase_FileSystem::FOLDER_TYPE_PREVIEWS,
            self::TYPE_ROOT
        ))) {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $containerType);
        }
        
        return $containerType;
    }
    
    /**
     * get container owner from path
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _getContainerOwner($_pathParts)
    {
        $containerOwner = ($this->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL && isset($_pathParts[3])) ? $_pathParts[3] : NULL;
        
        return $containerOwner;
    }
    
    /**
     * get application from path
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getApplication($_pathParts)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_pathParts[0]);
        
        return $application;
    }
    
    /**
     * do path replacements (container name => container id, account name => account id)
     * 
     * @param array $pathParts
     * @return string
     */
    protected function _getStatPath($pathParts = NULL)
    {
        if ($pathParts === NULL) {
            $pathParts = array(
                $this->application->getId(),
                'folders',
                $this->containerType,
            );
            
            if ($this->containerOwner) {
                $pathParts[] = $this->containerOwner;
            }

            if ($this->realpath) {
                $pathParts += explode('/', $this->realpath);
            }
            $this->flatpath = '/' . implode('/', $pathParts);
        }
        return $this->_createStatPathFromParts($pathParts);
    }
    
    /**
     * create stat path from path parts
     * 
     * @param array $pathParts
     * @return string
     */
    protected function _createStatPathFromParts($pathParts)
    {
        if (count($pathParts) > 3) {
            // replace account login name with id
            if ($this->containerOwner) {
                try {
                    $pathParts[3] = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $this->containerOwner, 'Tinebase_Model_FullUser')->getId();
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // try again with id
                    $accountId = is_object($this->containerOwner) ? $this->containerOwner->getId() : $this->containerOwner;
                    try {
                        $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $accountId, 'Tinebase_Model_FullUser');
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountDisplayName', $accountId, 'Tinebase_Model_FullUser');
                    }
                    $pathParts[3] = $user->getId();
                    $this->containerOwner = $user->accountLoginName;
                }
            }
        }
        
        $result = '/' . implode('/', $pathParts);
        return $result;
    }
    
    /**
     * get real path
     * 
     * @param array $pathParts
     * @return NULL|string
     */
    protected function _getRealPath($pathParts)
    {
        $result = NULL;
        $firstRealPartIdx = ($this->containerType === Tinebase_FileSystem::FOLDER_TYPE_SHARED) ? 4 : 5;
        if (isset($pathParts[$firstRealPartIdx])) {
            $result = implode('/', array_slice($pathParts, $firstRealPartIdx));
        }
        
        return $result;
    }

    /**
     * check if this path is on the top level (last part / name is personal. shared or user id)
     *
     * @return boolean
     */
    public function isToplevelPath()
    {
        $parts = $this->_getPathParts();
        return  (count($parts) == 3 &&
            (   $this->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL ||
                $this->containerType === Tinebase_FileSystem::FOLDER_TYPE_SHARED)) ||
                (count($parts) == 4 && $this->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
    }

    /**
     * check if this path is above the top level (/application|records|folders)
     *
     * @return boolean
     */
    public function isSystemPath()
    {
        $parts = $this->_getPathParts();
        return  (count($parts) < 3) ||
            (count($parts) == 3 && $this->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
    }


    /**
     * check if this path is the users personal path (/application/personal/account_id)
     *
     * @param string|Tinebase_Model_User          $_accountId
     * @return boolean
     */
    public function isPersonalPath($_accountId)
    {
        $account = $_accountId instanceof Tinebase_Model_FullUser
            ? $_accountId
            : Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_accountId, 'Tinebase_Model_FullUser');

        $parts = $this->_getPathParts();
        return (count($parts) == 4
            && $this->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            && ($parts[3] === $account->getId() || $parts[3] === $account->accountLoginName)
        );
    }

    /**
     * returns true if path belongs to a record or record attachment
     *
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function isRecordPath()
    {
        $parts = $this->_getPathParts();
        return (count($parts) > 2 && $parts[2] === self::RECORDS_PART);
    }

    /**
     * validate node/container existance
     * 
     * @throws Tinebase_Exception_NotFound
     */
    public function validateExistance()
    {
        if (! $this->containerType || ! $this->statpath) {
            $this->_parsePath();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Validate statpath: ' . $this->statpath);
        
        if (! Tinebase_FileSystem::getInstance()->fileExists($this->statpath)) {
            throw new Tinebase_Exception_NotFound('Node not found');
        }
    }

    /**
     * get node of path
     *
     * @return Tinebase_Model_Tree_Node
     */
    public function getNode()
    {
        return Tinebase_FileSystem::getInstance()->stat($this->statpath);
    }

    /**
     * return path user
     *
     * @return Tinebase_Model_FullUser
     *
     * TODO handle IDs or unresolved paths?
     */
    public function getUser()
    {
        if (! $this->user) {
            if ($this->containerOwner) {
                $this->user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend(
                    'accountLoginName',
                    $this->containerOwner,
                    'Tinebase_Model_FullUser'
                );
            }
        }

        return $this->user;
    }

    public function getRecordId()
    {
        if ($this->containerType === Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
            $pathparts = $this->_getPathParts();
            return $pathparts[4];
        } else {
            return null;
        }
    }

    public function getRecordModel()
    {
        if ($this->containerType === Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
            $pathparts = $this->_getPathParts();
            return $pathparts[3];
        } else {
            return null;
        }
    }
}
