<?php
/**
 * grants model of a container
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * grants model
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @property string         id
 * @property string         record_id
 * @property string         account_grant
 * @property string         account_id
 * @property string         account_type
 */
class Tinebase_Model_Grants extends Tinebase_Record_Abstract
{
    /**
     * grant to read all records of a container / a single record
     */
    const GRANT_READ     = 'readGrant';
    
    /**
     * grant to add a record to a container
     */
    const GRANT_ADD      = 'addGrant';
    
    /**
     * grant to edit all records of a container / a single record
     */
    const GRANT_EDIT     = 'editGrant';
    
    /**
     * grant to delete  all records of a container / a single record
     */
    const GRANT_DELETE   = 'deleteGrant';


    /**
     * grant to export all records of a container / a single record
     */
    const GRANT_EXPORT = 'exportGrant';
    
    /**
     * grant to sync all records of a container / a single record
     */
    const GRANT_SYNC = 'syncGrant';
    
    /**
     * grant to administrate a container
     */
    const GRANT_ADMIN    = 'adminGrant';


    /**
     * grant to download file nodes
     */
    const GRANT_DOWNLOAD = 'downloadGrant';

    /**
     * grant to publish nodes in Filemanager
     * @todo move to Filemanager_Model_Grant once we are able to cope with app specific grant classes
     */
    const GRANT_PUBLISH = 'publishGrant';

    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * constructor
     * 
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     */
    public function __construct($_data = null, $_bypassFilters = false, $_convertDates = null)
    {
        // sadly we need this outside the "null ===" if below as Tinebase_Model_Grants will be initialized most probably already
        if (static::class !== (new ReflectionProperty(static::class, '_modelConfiguration'))->getDeclaringClass()->getName()) {
            throw new Tinebase_Exception_Record_DefinitionFailure(static::class . ' doesn\'t declare _modelConfiguration');
        }
        if (null === static::$_modelConfiguration) {
            if (static::class !== (new ReflectionProperty(static::class, '_configurationObject'))->getDeclaringClass()->getName()) {
                throw new Tinebase_Exception_Record_DefinitionFailure(static::class . ' doesn\'t declare _configurationObject');
            }
            preg_match('/^([^_]+)_Model_(.*)$/', static::class, $m);
            $application = $m[1];
            $model = $m[2];
            if ($this->_application !== $application) {
                throw new Tinebase_Exception_Record_DefinitionFailure(static::class . ' declares wrong application: ' .
                    $this->_application . ' !== ' . $application);
            }
            static::$_modelConfiguration = [
                self::APP_NAME => $this->_application,
                self::MODEL_NAME => $model,
                self::TITLE_PROPERTY => 'account_id',
                self::RECORD_NAME => 'Grant', // gettext('GENDER_Grant')
                self::RECORDS_NAME => 'Grants', // ngettext('Grant', 'Grants', n)

                self::FIELDS => [
                    'record_id'     => [
                        self::TYPE      => self::TYPE_STRING,
                        self::VALIDATORS => array('allowEmpty' => true),
                    ],
                    'account_grant' => [
                        self::TYPE      => self::TYPE_STRING,
                        self::VALIDATORS => array('allowEmpty' => true),
                    ],
                    'account_id'    => [
                        self::TYPE      => self::TYPE_STRING,
                        self::VALIDATORS => array('presence' => 'required', 'allowEmpty' => true, 'default' => '0'),
                    ],
                    'account_type'  => [
                        self::TYPE      => self::TYPE_STRING,
                        self::VALIDATORS => array('presence' => 'required', array('InArray', array(
                            Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                            Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                            Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE
                        ))),
                    ],
                    'account_name' => [
                        self::TYPE      => self::TYPE_VIRTUAL,
                    ],
                ],
            ];

            $allGrantsMC = static::getAllGrantsMC();
            foreach (static::getAllGrants() as $grant) {
                static::$_modelConfiguration[self::FIELDS][$grant] = array_merge([
                    self::TYPE      => self::TYPE_BOOLEAN,
                    self::VALIDATORS => array(
                        new Zend_Validate_InArray(array(true, false), true),
                        'default' => false,
                        'presence' => 'required',
                        'allowEmpty' => true
                    ),
                ], isset($allGrantsMC[$grant]) ? $allGrantsMC[$grant] : []);
            }
        }

        parent::__construct($_data, $_bypassFilters, $_convertDates);

        foreach ($this->getAllGrants() as $grant) {
            if (! $this->__isset($grant)) {
                // initialize in case validators are switched off
                $this->{$grant} = false;
            }
        }
    }
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_ADD,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE,
            self::GRANT_EXPORT,
            self::GRANT_SYNC,
            self::GRANT_ADMIN,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY,
            self::GRANT_DOWNLOAD,
            self::GRANT_PUBLISH,
        );
    
        return $allGrants;
    }

    public static function getAllGrantsMC(): array
    {
        return [
            self::GRANT_READ    => [
                self::LABEL         => 'read',
            ],
        ];
    }

    public function appliesToUser(Tinebase_Model_FullUser $user): bool
    {
        switch ($this->account_type) {
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                if (! in_array($user->getId(), Tinebase_Group::getInstance()->getGroupMembers($this->account_id))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Current user not member of group ' . $this->account_id);
                    return false;
                }
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                if ($user->getId() !== $this->account_id) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Grant not available for current user (account_id of grant: ' . $this->account_id . ')');
                    return false;
                }
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                $userId = $user->getId();
                foreach (Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->account_id) as $roleMember) {
                    if (Tinebase_Acl_Rights::ACCOUNT_TYPE_USER === $roleMember['account_type'] &&
                        $userId === $roleMember['account_id']) {
                        return true;
                    }
                    if (Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP === $roleMember['account_type'] &&
                        in_array($user->getId(), Tinebase_Group::getInstance()->getGroupMembers($roleMember['account_id']))) {
                        return true;
                    }
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Current user not member of role ' . $this->account_id);
                return false;
        }

        return true;
    }

    /**
     * checks record grant
     * 
     * @param string $grant
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     */
    public function userHasGrant($grant, Tinebase_Model_FullUser $user = null)
    {
        if ($user === null) {
            $user = Tinebase_Core::getUser();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Check grant ' . $grant . ' for user ' . $user->getId() . ' in ' . print_r($this->toArray(), true));
        
        if (! is_object($user)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' No user object');
            return false;
        }
        
        if (! in_array($grant, $this->getAllGrants()) || ! isset($this->{$grant}) || ! $this->{$grant}) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Grant not defined or not set');
            return false;
        }
        
        return $this->appliesToUser($user);
    }

    /**
     * fills record with all grants and adds account id
     */
    public function sanitizeAccountIdAndFillWithAllGrants()
    {
        if (empty($this->account_id)) {
            if ($this->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && 
                is_object(Tinebase_Core::getUser())) 
            {
                $this->account_id = Tinebase_Core::getUser()->getId();
            } else if ($this->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP || 
                Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED))
            {
                $this->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP;
                $this->account_id = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
            } else {
                $this->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                $this->account_id = 0;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Set all available grants for ' . $this->account_type . ' with id ' . $this->account_id);
        
        foreach ($this->getAllGrants() as $grant) {
            $this->$grant = true;
        }
        
        return $this;
    }

    /**
     * return default grants with read for user group, write/admin for current user and write/admin for admin group
     *
     * @param array $_additionalGrants
     * @param array $_additionalAdminGrants
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grants
     */
    public static function getDefaultGrants($_additionalGrants = array(), $_additionalAdminGrants = array())
    {
        $groupsBackend = Tinebase_Group::getInstance();
        $adminGrants = array_merge(array_merge([
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_ADD => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_DELETE => true,
            Tinebase_Model_Grants::GRANT_ADMIN => true,
            Tinebase_Model_Grants::GRANT_EXPORT => true,
            Tinebase_Model_Grants::GRANT_SYNC => true,
        ], $_additionalGrants), $_additionalAdminGrants);
        $grants = [
            array_merge([
                'account_id' => $groupsBackend->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
            ], $_additionalGrants),
            array_merge([
                'account_id' => $groupsBackend->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            ], $adminGrants),
        ];

        if (is_object(Tinebase_Core::getUser())) {
            $grants[] = array_merge([
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            ], $adminGrants);
        }

        return new Tinebase_Record_RecordSet(static::class, $grants, true);
    }

    /**
     * return personal grants for given account
     *
     * @param string|Tinebase_Model_User          $_accountId
     * @param array $_additionalGrants
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grants
     */
    public static function getPersonalGrants($_accountId, $_additionalGrants = array())
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $grants = array(Tinebase_Model_Grants::GRANT_READ      => true,
            Tinebase_Model_Grants::GRANT_ADD       => true,
            Tinebase_Model_Grants::GRANT_EDIT      => true,
            Tinebase_Model_Grants::GRANT_DELETE    => true,
            Tinebase_Model_Grants::GRANT_EXPORT    => true,
            Tinebase_Model_Grants::GRANT_SYNC      => true,
            Tinebase_Model_Grants::GRANT_ADMIN     => true,
        );
        $grants = array_merge($grants, $_additionalGrants);
        return new Tinebase_Record_RecordSet(static::class, array(array_merge(array(
            'account_id'     => $accountId,
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
        ), $grants)));
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSet
     * @param Tinebase_Record_RecordSetDiff $_recordSetDiff
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function applyRecordSetDiff(Tinebase_Record_RecordSet $_recordSet, Tinebase_Record_RecordSetDiff $_recordSetDiff)
    {
        $model = $_recordSetDiff->model;
        if ($_recordSet->getRecordClassName() !== $model) {
            throw new Tinebase_Exception_InvalidArgument('try to apply record set diff on a record set of different model!' .
                'record set model: ' . $_recordSet->getRecordClassName() . ', record set diff model: ' . $model);
        }

        /** @var Tinebase_Record_Interface $modelInstance */
        $modelInstance = new $model(array(), true);
        $idProperty = $modelInstance->getIdProperty();

        foreach($_recordSetDiff->removed as $data) {
            $found = false;
            /** @var Tinebase_Model_Grants $record */
            foreach ($_recordSet as $record) {
                if (    $record->record_id      === $data['record_id']      &&
                        $record->account_id     === $data['account_id']     &&
                        $record->account_type   === $data['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $_recordSet->removeRecord($record);
            }
        }

        foreach($_recordSetDiff->modified as $data) {
            $diff = new Tinebase_Record_Diff($data);
            $found = false;
            /** @var Tinebase_Model_Grants $record */
            foreach ($_recordSet as $record) {
                if (    $record->record_id      === $diff->diff['record_id']      &&
                        $record->account_id     === $diff->diff['account_id']     &&
                        $record->account_type   === $diff->diff['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $record->applyDiff($diff);
            } else {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Did not find the record supposed to be modified with id: ' . $data[$idProperty]);
                throw new Tinebase_Exception_InvalidArgument('Did not find the record supposed to be modified with id: ' . $data[$idProperty]);
            }
        }

        foreach($_recordSetDiff->added as $data) {
            $found = false;
            /** @var Tinebase_Model_Grants $record */
            foreach ($_recordSet as $record) {
                if (    $record->record_id      === $data['record_id']      &&
                        $record->account_id     === $data['account_id']     &&
                        $record->account_type   === $data['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $_recordSet->removeRecord($record);
            }
            $newRecord = new $model($data);
            $_recordSet->addRecord($newRecord);
        }

        return true;
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSetOne
     * @param Tinebase_Record_RecordSet $_recordSetTwo
     * @param ?Tinebase_Record_DiffContext $context
     * @return null|Tinebase_Record_RecordSetDiff
     */
    public static function recordSetDiff(Tinebase_Record_RecordSet $_recordSetOne, Tinebase_Record_RecordSet $_recordSetTwo, ?Tinebase_Record_DiffContext $context = null)
    {
        $shallowCopyTwo = new Tinebase_Record_RecordSet(static::class);
        $removed = new Tinebase_Record_RecordSet(static::class);
        $added = new Tinebase_Record_RecordSet(static::class);
        $modified = new Tinebase_Record_RecordSet('Tinebase_Record_Diff');

        foreach ($_recordSetTwo as $grantTwo) {
            $shallowCopyTwo->addRecord($grantTwo);
        }

        /** @var Tinebase_Model_Grants $grantOne */
        foreach ($_recordSetOne as $grantOne) {
            $found = false;
            /** @var Tinebase_Model_Grants $grantTwo */
            foreach ($shallowCopyTwo as $grantTwo) {
                if (    $grantOne->record_id      === $grantTwo->record_id      &&
                        $grantOne->account_id     === $grantTwo->account_id     &&
                        $grantOne->account_type   === $grantTwo->account_type       ) {
                    $found = true;
                    break;
                }
            }

            if (true === $found) {
                $shallowCopyTwo->removeRecord($grantTwo);
                $diff = $grantOne->diff($grantTwo, array('id', 'account_grant'));
                if (!$diff->isEmpty()) {
                    $diff->xprops('diff')['record_id']    = $grantTwo->record_id;
                    $diff->xprops('diff')['account_id']   = $grantTwo->account_id;
                    $diff->xprops('diff')['account_type'] = $grantTwo->account_type;
                    $diff->xprops('oldData')['record_id']    = $grantTwo->record_id;
                    $diff->xprops('oldData')['account_id']   = $grantTwo->account_id;
                    $diff->xprops('oldData')['account_type'] = $grantTwo->account_type;
                    $modified->addRecord($diff);
                }
            } else {
                $removed->addRecord($grantOne);
            }
        }

        /** @var Tinebase_Model_Grants $grantTwo */
        foreach ($shallowCopyTwo as $grantTwo) {
            $added->addRecord($grantTwo);
        }

        $result = new Tinebase_Record_RecordSetDiff(array(
            'model'    => static::class,
            'added'    => $added,
            'removed'  => $removed,
            'modified' => $modified,
        ));

        return $result;
    }

    /**
     * @return bool
     */
    public static function doSetGrantFailsafeCheck()
    {
        return true;
    }

    /**
     * @param Zend_Db_Select $_select
     * @param Tinebase_Model_Application $_application
     * @param string $_accountId
     * @param string|array $_grant
     */
    public static function addCustomGetSharedContainerSQL(Zend_Db_Select $_select,
        Tinebase_Model_Application $_application, $_accountId, $_grant)
    {
    }

    /**
     * @return array
     */
    public static function resolveGrantAccounts($grants)
    {
        $accounts = [];
        switch ($grants['account_type']) {
            case 'user': 
                $accounts[] = $grants['account_id'];
                break;
            case 'group':
                $accounts = Tinebase_Group::getInstance()->getGroupMembers($grants['account_id']);
                break;
            case 'role':
                $accounts = Tinebase_Role::getInstance()->getRoleMembers($grants['account_id']);
                break;
        }
       
        return $accounts;
    }
}
