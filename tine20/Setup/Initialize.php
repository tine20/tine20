<?php
/**
 * Tine 2.0
  * 
 * @package     Setup
 * @subpackage  Initialize
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application initialization
 * 
 * @package     Setup
 * @subpackage  Initialize
 */
class Setup_Initialize
{
    /**
     * array with user role rights, overwrite this in your app to add more rights to user role
     * 
     * @var array
     */
    static protected $_userRoleRights = array(
        Tinebase_Acl_Rights::RUN,
        Tinebase_Acl_Rights::MAINSCREEN,
    );
    
    /**
     * Call {@see _initialize} on an instance of the concrete Setup_Initialize class for the given {@param $_application}  
     * 
     * @param Tinebase_Model_Application $_application
     * @param array|null $_options
     * @return void
     */
    public static function initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        $applicationName = $_application->name;
        $classname = "{$applicationName}_Setup_Initialize";

        if (class_exists($classname)) {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing application: ' . $applicationName);

            // custom init might need a valid user
            if (! is_object(Tinebase_Core::getUser()) && ! in_array($applicationName, array('Setup', 'Tinebase', 'Addressbook', 'Admin'))) {
                $user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
                if ($user) {
                    Tinebase_Core::set(Tinebase_Core::USER, $user);
                }
            }

            $instance = new $classname;
            $instance->_initialize($_application, $_options);
        } else {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skipping custom init of application: '
                . $applicationName . '. Class ' . $classname . ' not found.');

            self::createInitialRights($_application);
        }

        // prefill applications update state
        $now = Tinebase_DateTime::now()->toString();
        $appMajorV = (int)$_application->getMajorVersion();
        $updates = [];
        for ($majorV = 0; $majorV <= $appMajorV; ++$majorV) {
            /** @var Setup_Update_Abstract $class */
            $class = $_application->name . '_Setup_Update_' . $majorV;
            if (class_exists($class)) {
                $updates += array_values($class::getAllUpdates());
            }
        }
        if (!empty($updates)) {
            array_walk($updates, function (&$val) { $val = key($val); });
            Tinebase_Application::getInstance()->setApplicationState($_application, Tinebase_Application::STATE_UPDATES,
                json_encode(array_fill_keys($updates, $now)));
        }
    }
    
    /**
     * Call {@see createInitialRights} on an instance of the concrete Setup_Initialize class for the given {@param $_application}
     * 
     * @param Tinebase_Model_Application $_application
     * @return void
     */
    public static function initializeApplicationRights(Tinebase_Model_Application $_application)
    {
        $applicationName = $_application->name;
        $classname = "{$applicationName}_Setup_Initialize";
        $instance = new $classname;
        $instance::createInitialRights($_application);
    }
    
    /**
     * initialize application
     *
     * @param Tinebase_Model_Application $_application
     * @param array|null $_options
     * @return void
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        self::initializeApplicationRights($_application);
        $initClasses = array($this);

        $customInitClass = $_application->name . '_Setup_Initialize_Custom';
        if (class_exists($customInitClass)) {
            $customInit = new $customInitClass();
            $initClasses[] = $customInit;
        }

        foreach ($initClasses as $initClass) {
            $reflectionClass = new ReflectionClass($initClass);
            $methods = $reflectionClass->getMethods();
            foreach ($methods as $method) {
                $methodName = $method->name;
                if ((strpos($methodName, '_initialize') === 0 && $methodName !== '_initialize')
                    || (get_class($initClass) === $customInitClass && (strpos($methodName, 'initialize') === 0))
                ) {
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Calling init function '
                        . get_class($initClass) . '::' . $methodName);

                    $initClass->$methodName($_application, $_options);
                }
            }
        }
    }
    
    /**
     * create inital rights
     * 
     * @param Tinebase_Model_Application $_application
     * @return void
     */
    public static function createInitialRights(Tinebase_Model_Application $_application)
    {
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial rights for application '
            . $_application->name);

        $allRights = Tinebase_Application::getInstance()->getAllRights($_application->getId());
        $userRights = static::$_userRoleRights;
        
        if (in_array(Tinebase_Acl_Rights::USE_PERSONAL_TAGS, $allRights)) {
            $userRights[] = Tinebase_Acl_Rights::USE_PERSONAL_TAGS;
        }

        $userRoleName = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_USER_ROLE_NAME);
        $adminRoleName = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_ADMIN_ROLE_NAME);
        $roleRights = array(
            $userRoleName     => $userRights,
            $adminRoleName    => $allRights
        );
        
        foreach ($roleRights as $roleName => $rights) {
            try {
                $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($roleName);
            } catch(Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                continue;
            }
            
            foreach ($rights as $right) {
                try {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight($role->getId(), $_application->getId(), $right);
                } catch(Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' Cannot add right: ' . $right . ' for application: ' . $_application->name
                        . ' - ' . $roleName . ' - ' . print_r($e->getMessage(), true)
                    );
                }
            }
        }
    }

    /**
     * create application customfields
     *
     * expects $customFields with the following structure:
     *
     * $customfields = [
     *      [
     *          'app' => 'Addressbook',
     *          'model' => Addressbook_Model_Contact::class,
     *          'cfields' => [
     *              [
     *                  'name' => 'community_ident_nr',
     *                  'label' => 'Amtlicher Regionalschlüssel',
     *                  'uiconfig' => [
     *                      'order' => '',
     *                      'group' => '',
     *                      'tab' => ''
     *                  ],
     *                  'type' => 'string',
     *              ]
     *          ]
     *      ],
     *      [...]
     * ]
     *
     * @param array $customfields
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public static function createCustomFields(array $customfields)
    {
        foreach ($customfields as $appModel) {
            $appId = Tinebase_Application::getInstance()->getApplicationByName($appModel['app'])->getId();

            foreach ($appModel['cfields'] as $customfield) {
                $cfc = array(
                    'name' => $customfield['name'],
                    'application_id' => $appId,
                    'model' => $appModel['model'],
                    'definition' => array(
                        'uiconfig' => $customfield['uiconfig'],
                        'label' => $customfield['label'],
                        'type' => $customfield['type'],
                    )
                );

                if ($customfield['type'] == 'record') {
                    $cfc['definition']['recordConfig'] = $customfield['recordConfig'];
                } elseif ($customfield['type'] == 'keyField') {
                    $cfc['definition']['keyFieldConfig'] = $customfield['recordConfig'];
                }

                $cf = new Tinebase_Model_CustomField_Config($cfc);
                Tinebase_CustomField::getInstance()->addCustomField($cf);
            }
        }
    }

    /**
     * expects $tags with the following structure:
     *
     * $tags = [
     *  [
     *      'name' => 'Mitglied',
     *      'description' => 'gehört zu einem Mitglied',
     *      'color' => '#339966',
     *      'config' => [
     *          'appName' => 'APP'
     *          'configkey' => APP::CONFIG, //string - if given, tag id is saved in this config
     *       ],
     *  ], [...]
     * ]
     *
     * @param array $tags
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public static function createSharedTags(array $tags)
    {
        $controller = Tinebase_Tags::getInstance();

        foreach ($tags as $tag) {
            $sharedTag = new Tinebase_Model_Tag(array(
                'type' => Tinebase_Model_Tag::TYPE_SHARED,
                'name' => $tag['name'],
                'description' => $tag['description'],
                'color' => $tag['color'],
                'system_tag' => $tag['system_tag'] ?? false
            ));

            $savedSharedTag = $controller->createTag($sharedTag);
            $controller->setContexts(array('any'), $savedSharedTag->getId());

            $right = new Tinebase_Model_TagRight(array(
                'tag_id' => $savedSharedTag->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'account_id' => 0,
                'view_right' => true,
                'use_right' => !$tag['system_tag'],
            ));
            $controller->setRights($right);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Created shared tag ' . $savedSharedTag->name);

            if (isset($tag['config']) && isset($tag['config']['appName']) && isset($tag['config']['configkey'])) {
                $appConfig = Tinebase_Config::getAppConfig($tag['config']['appName']);
                if ($appConfig) {
                    $appConfig->
                    set(
                        $tag['config']['configkey'],
                        $savedSharedTag->getId()
                    );
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' No config found for ' . $tag['config']['appName']);
                }
            }
        }
    }
}
