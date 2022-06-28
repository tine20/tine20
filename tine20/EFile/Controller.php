<?php
/**
 * EFile Controller
 *
 * @package      EFile
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * EFile Controller
 *
 * @package      EFile
 * @subpackage   Controller
 */
class EFile_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    const TIER_TOKEN_SEPERATOR = ' - ';

    protected $_applicationName = EFile_Config::APP_NAME;

    /**
     * @var bool
     */
    protected $_doTreeNodeValidation = true;

    /**
     * @var string
     */
    protected static $_useEFileType = null;

    /**
     * @var bool
     */
    protected static $_allowExtendedDefaults = false;

    /**
     * @return bool
     */
    public function doTreeNodeValidation()
    {
        return $this->_doTreeNodeValidation;
    }

    /**
     * @param $bool
     * @return void
     */
    public function setDotreeNodeValidation($bool)
    {
        $this->_doTreeNodeValidation = (bool)$bool;
    }

    /**
     * @param $bool
     * @return void
     */
    public static function setAllowExtendedDefaults($bool)
    {
        self::$_allowExtendedDefaults = (bool)$bool;
    }

    /**
     * @param string $path
     * @param string $type
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function createEFileFolder($path, $type)
    {
        $raii = new Tinebase_RAII(function () {
            static::$_useEFileType = null;
        });
        static::$_useEFileType = $type;

        // fail if parent dir doesnt exist! (validation only ever validates one tier level at oncereally? not anymore?)
        $fs = Tinebase_FileSystem::getInstance();
        $fs->stat(dirname($path));
        $node = $fs->mkdir($path);

        unset($raii);
        return $node;
    }

    /**
     * called by customfield modelconfig hook whenever the first Tinebase_Model_Tree_Node model gets created
     *
     * @return void
     */
    public static function registerTreeNodeHooks()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->_getTreeNodeBackend()->registerBeforeCreateHook(self::class, [self::class, 'checkEFileNode']);
        $fs->_getTreeNodeBackend()->registerAfterCreateHook(self::class, [self::class, 'checkMetaData']);
        $fs->_getTreeNodeBackend()->registerBeforeUpdateHook(self::class, [self::class, 'checkEFileNode']);
        $fs->_getTreeNodeBackend()->registerAfterUpdateHook(self::class, [self::class, 'afterEFileNodeUpdate']);
        $fs->_getTreeNodeBackend()->registerAfterUpdateHook(self::class . '::checkMetaData', [self::class, 'checkMetaData']);

        $fm = Filemanager_Controller_Node::getInstance();
        $fm->registerCreateNodeInBackendInterceptor(self::class, [self::class, 'FMcreateNodeIntercepter']);
        $fm->registerMoveNodesHook(self::class, [self::class, 'FMmoveNodesHook']);
        $fm->addAllowedProperty(EFile_Config::TREE_NODE_FLD_FILE_METADATA);
        $fm->addAllowedProperty(EFile_Config::TREE_NODE_FLD_TIER_TYPE);
    }

    public static function FMmoveNodesHook()
    {
        EFile_Controller::setAllowExtendedDefaults(true);
        return new Tinebase_RAII(function() {
            EFile_Controller::setAllowExtendedDefaults(false);
        });
    }

    public static function FMcreateNodeIntercepter($_statpath, $_type, $_tempFileId)
    {
        $context = Filemanager_Controller_Node::getInstance()->getRequestContext();
        $headerToken = str_replace('_', '-', EFile_Config::TREE_NODE_FLD_TIER_TYPE);
        if (!isset($context['clientData'][$headerToken])) return null;

        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_type) {
            return static::getInstance()->createEFileFolder($_statpath, $context['clientData'][$headerToken]);
        }

        return null;
    }

    public static function checkMetaData(Tinebase_Model_Tree_Node $_newRecord, Tinebase_Model_Tree_Node $_orgRecord)
    {
        if (EFile_Model_EFileTierType::TIER_TYPE_FILE !== $_newRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
            return;
        }
        $metaData = EFile_Controller_FileMetadata::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(EFile_Model_FileMetadata::class, [
                ['field' => EFile_Model_FileMetadata::FLD_NODE_ID, 'operator' => 'equals', 'value' => $_newRecord->getId()]
            ]))->getFirstRecord();

        if (!$metaData) {
            if (null === ($contact = Addressbook_Config::getInstallationRepresentative())) {
                $str = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) ?: 'tine20';
            } else {
                $str = $contact->n_fileas;
            }
            $metaData = new EFile_Model_FileMetadata([
                EFile_Model_FileMetadata::FLD_DURATION_START        => Tinebase_DateTime::now(),
                EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE   => $str,
                EFile_Model_FileMetadata::FLD_NODE_ID               => $_newRecord->getId(),
            ]);
            EFile_Controller_FileMetadata::getInstance()->create($metaData);
        }
    }

    public static function afterEFileNodeUpdate(Tinebase_Model_Tree_Node $_newRecord, Tinebase_Model_Tree_Node $_oldRecord)
    {
        $instance = static::getInstance();
        // check preconditions if to run at all
        if (!$instance->doTreeNodeValidation()) return;

        if ($_newRecord->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} && strpos($_newRecord->name,
                $_newRecord->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} . self::TIER_TOKEN_SEPERATOR) !== 0) {
            throw new Tinebase_Exception_Record_Validation('node name needs to start with efile tier token');
        }
        // moving a document dir inside a docuement dir hierachy, the ref_number will not change, so also no replica of structure will not be created
        // if ref number changed, rename all children
        if ($_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} && $_newRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} !== $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER}) {
            Tinebase_FileSystem::getInstance()->clearStatCache();
            $oldValidation = $instance->doTreeNodeValidation();
            $instance->setDotreeNodeValidation(false);
            try {
                // create replica structure / links in old location, do that BEFORE renaming, saves naming effort
                if ($_oldRecord->parent_id !== $_newRecord->parent_id) {
                    static::replicateStructure($_newRecord, clone $_oldRecord);
                }

                if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER !== $_newRecord->type) {
                    return;
                }

                Tinebase_FileSystem::getInstance()->getFromStatCache($_newRecord->getId())->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = [];
                foreach (Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()
                            ->getChildren($_newRecord->getId(), true)->sort(function($val, $val1) {
                                return strcmp(ltrim($val->name, '#'), ltrim($val1->name, '#'));
                            }) as $child) {
                    static::nameChildByParent($_newRecord, $child, true, true);
                }
            } finally {
                $instance->setDotreeNodeValidation($oldValidation);
            }
        }

        if (! $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} && $_newRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} &&
                $_oldRecord->parent_id !== $_newRecord->parent_id && Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_newRecord->type) {
            Tinebase_FileSystem::getInstance()->clearStatCache();
            $oldValidation = $instance->doTreeNodeValidation();
            $instance->setDotreeNodeValidation(false);
            try {
                foreach (Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()
                             ->getChildren($_newRecord->getId(), true)->sort('name') as $child) {
                    static::nameChildByParent($_newRecord, $child, true, true);
                }
            } finally {
                $instance->setDotreeNodeValidation($oldValidation);
            }
        }
    }

    protected static function setExtendedDefaults($_parent, $_child)
    {
        if ($_child->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
            if (in_array($_parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}, [
                EFile_Model_EFileTierType::TIER_TYPE_FILE,
                EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                EFile_Model_EFileTierType::TIER_TYPE_CASE,
                EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
            ])) {
                $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT;
            }
        } elseif (static::$_allowExtendedDefaults) {
            switch ($_parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                case EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN:
                    $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP;
                    break;
                case EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP:
                    $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_FILE;
                    break;
                case EFile_Model_EFileTierType::TIER_TYPE_FILE:
                case EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE:
                case EFile_Model_EFileTierType::TIER_TYPE_CASE:
                case EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR:
                    $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR;
                    break;
            }
        }
    }

    public static function nameChildByParent($_parent, $_child, $_recursive = false, $_updateChild = false)
    {
        // strip token from node name
        if (strlen($_child->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN}) > 0 && strpos($_child->name,
                $_child->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} . self::TIER_TOKEN_SEPERATOR) === 0) {
            $_child->name = substr($_child->name, strlen($_child->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN}) +
                strlen(self::TIER_TOKEN_SEPERATOR));
        }

        if (!$_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
            self::setExtendedDefaults($_parent, $_child);
        }

        $prefixConf = EFile_Config::getInstance()->{EFile_Config::TIER_REFNUMBER_PREFIX};
        $tierType = $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE};
        if (EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN === $tierType &&
                !$_parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
            $prefix = $prefixConf[EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN_ROOT];
        } else {
            $prefix = $prefixConf[$tierType];
        }

        $tokenTemplateConf = EFile_Config::getInstance()->{EFile_Config::TIER_TOKEN_TEMPLATE};
        $tierToken = '';
        if (isset($tokenTemplateConf[$tierType])) {
            $tokenTemplate = $tokenTemplateConf[$tierType];

            switch ($tierType) {
                case EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT:
                    $counterNode = static::getEFileNonDocumentDirNode($_child);
                    break;
                case EFile_Model_EFileTierType::TIER_TYPE_CASE:
                    $counterNode = static::getApplicationNode($_child);
                    break;
                default:
                    $counterNode = $_parent;
            }


            $counter = $counterNode->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER};
            $counterTierType = $tierType === EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP ?
                EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN : $tierType;
            if (!is_array($counter)) $counter = [];
            if (!isset($counter[$counterTierType])) {
                $counter[$counterTierType] = 1;
            } else {
                $counter[$counterTierType] += 1;
            }
            $counterNode->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = $counter;
            Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->updateMultiple([$counterNode->getId()], [
                EFile_Config::TREE_NODE_FLD_TIER_COUNTER => json_encode($counter)
            ]);

            $tierToken = sprintf($tokenTemplate, $counter[$counterTierType]);

            $namePart = $tierToken . self::TIER_TOKEN_SEPERATOR;
            if (strpos($_child->name, $namePart) !== 0) {
                $_child->name = $namePart . $_child->name;
            }
        }
        $_child->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} = $tierToken;
        $_child->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} =
            $_parent->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} . $prefix . $tierToken;

        if ($_updateChild) {
            Tinebase_FileSystem::getInstance()->update($_child);
            if ($_recursive && Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_child->type) {
                Tinebase_FileSystem::getInstance()->getFromStatCache($_child->getId())
                    ->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = [];
            }
        }

        if ($_recursive && Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $_child->type) {
            foreach (Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->getChildren($_child->getId(), true)
                        ->sort(function($val, $val1) {
                            return strcmp(ltrim($val->name, '#'), ltrim($val1->name, '#'));
                        }) as $child) {
                static::nameChildByParent($_child, $child, true, $_updateChild);
            }
        }
    }

    public static function replicateStructure(Tinebase_Model_Tree_Node $_newRecord, Tinebase_Model_Tree_Node $_oldRecord)
    {
        if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $_oldRecord->type) {
            $_oldRecord->type = Tinebase_Model_Tree_FileObject::TYPE_LINK;
            $_oldRecord->linkto = $_newRecord->getId();
        }

        $node = Tinebase_FileSystem::getInstance()->createFileTreeNode($_oldRecord->parent_id, $_oldRecord->name, $_oldRecord->type);

        if (Tinebase_Model_Tree_FileObject::TYPE_LINK === $_oldRecord->type) {
            $node->linkto = $_oldRecord->linkto;
        }
        
        $node->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN};
        $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER};
        $node->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE};
        $node = Tinebase_FileSystem::getInstance()->update($node);

        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER !== $_oldRecord->type) {
            $fob = Tinebase_FileSystem::getInstance()->getFileObjectBackend();
            /** @var Tinebase_Model_Tree_FileObject $fObject */
            $fObject = $fob->get($node->object_id);
            if ($fObject->contenttype !== $_oldRecord->contenttype) {
                $fObject->contenttype = $_oldRecord->contenttype;
                $fob->update($fObject);
            }
            return;
        }

        foreach (Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->getChildren($_newRecord->getId(), true) as $child) {
            $_oldRecord = clone $child;
            $_oldRecord->parent_id = $node->getId();
            static::replicateStructure($child, $_oldRecord);
        }
    }

    public static function checkEFileNode(Tinebase_Model_Tree_Node $_record, Tinebase_Model_Tree_Node $_oldRecord = null)
    {
        // check preconditions if to run at all
        if (!static::getInstance()->doTreeNodeValidation()) return;

        if (static::$_useEFileType) {
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = static::$_useEFileType;
            static::$_useEFileType = null;
        }

        if (null === ($parent = static::getEFilesParentNode($_record))) {
            if ($_oldRecord && $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                throw new Tinebase_Exception_Record_Validation('eFile tier type can not be removed');
            }
            return;
        }

        // prevent spoofing
        if ($_oldRecord) {
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN};
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER};
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER};

            // TODO if allowSetDefaults
            if (! $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                static::setExtendedDefaults($parent, $_record);
            }
        } else {
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN} = null;
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER} = null;
            $_record->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = null;

            // set defaults, only on !$_oldRecord
            if (!$_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                if ($_record->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                    if (in_array($parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}, [
                        EFile_Model_EFileTierType::TIER_TYPE_FILE,
                        EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                        EFile_Model_EFileTierType::TIER_TYPE_CASE,
                        EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
                    ])) {
                        $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT;
                    }
                }
            }
        }

        // check if node was moved
        $resetOldRecordTierType = null;
        if ($_oldRecord && $_oldRecord->parent_id !== $_record->parent_id) {

            // subfile moved to a file group becomes a file
            if (EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE === $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}
                && EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP === $parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_FILE;

                // better way to do this?! otherwise LIVR will fail
                $resetOldRecordTierType = $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE};
                $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = EFile_Model_EFileTierType::TIER_TYPE_FILE;
            }
        }

        // do LIVR
        $data = $_record->toArray(false);
        $data['parent'] = $parent->toArray(false);
        $data['parent']['path'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath(
            Tinebase_FileSystem::getInstance()->getPathOfNode($parent->getId(), true), 'Filemanager') . '/';

        if ($_oldRecord) {
            $data['oldrecord'] = $_oldRecord->toArray(false);
            if ($_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} !== $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                $data['children'] = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($_record->getId())->toArray();
            }
        }

        $validator = static::getLIVRValidator();
        if (false === $validator->validate($data)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__CLASS__ . '::' .
                __LINE__ . ' eFile LIVR validation failed: ' . print_r($validator->getErrors(), true));
            throw new Tinebase_Exception_Record_Validation('eFile validation failed');
        }
        if ($resetOldRecordTierType) {
            $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} = $resetOldRecordTierType;
        }

        // check if node was moved or if tier type did change => rename stuff
        $generateTierToken = false;
        if ($_oldRecord && ($_oldRecord->parent_id !== $_record->parent_id ||
                $_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} !== $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE})) {
            $generateTierToken = true;
        }

        // generate tier token, adjust name and set tier reference number
        if (!$_oldRecord || !$_oldRecord->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} || $generateTierToken) {
            static::nameChildByParent($parent, $_record);
        }

        $namePart = substr($_record->name, strlen($_record->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN}) +
            strlen(self::TIER_TOKEN_SEPERATOR));
        foreach (EFile_Config::getInstance()->{EFile_Config::NODE_NAME_DENIED_SUBSTRINGS} as $denySubstr) {
            if (strpos($namePart, $denySubstr) !== false) {
                $translation = Tinebase_Translation::getTranslation('EFile');
                $message = $translation->_('EFile node names may not contain:') . ' ' . $denySubstr;
                throw new Tinebase_Exception_SystemGeneric($message);
            }
        }
    }

    /**
     * checks whether the current node is an EFile or if the parent node is an EFile and if so, returns parent
     * otherwise null is returned
     *
     * @param Tinebase_Model_Tree_Node $_record
     * @return Tinebase_Model_Tree_Node|null
     */
    public static function getEFilesParentNode(Tinebase_Model_Tree_Node $_record)
    {
        if (!$_record->parent_id) {
            if ($_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                throw new Tinebase_Exception_Record_Validation('eFile nodes need to have a parent');
            }
            return null;
        }
        $parent = Tinebase_FileSystem::getInstance()->getFromStatCache($_record->parent_id);
        if ($parent->{EFile_Config::TREE_NODE_FLD_TIER_TYPE} || $_record->{EFile_Config::TREE_NODE_FLD_TIER_TYPE})
            return $parent;
        return null;
    }

    /**
     * get application node /EFile
     *
     * @return Tinebase_Model_Tree_Node|null
     * @throws Tinebase_Exception_NotFound
     */
    public static function getApplicationNode()
    {
        try {
            $node = Tinebase_FileSystem::getInstance()->stat('/' .
                Tinebase_Application::getInstance()->getApplicationByName(EFile_Config::APP_NAME)->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $node = Tinebase_FileSystem::getInstance()->mkdir('/' .
                Tinebase_Application::getInstance()->getApplicationByName(EFile_Config::APP_NAME)->getId());
        }
        return $node;
    }

    /**
     * get closest parent that is not document dir
     *
     * @param Tinebase_Model_Tree_Node $_child
     * @return Tinebase_Model_Tree_Node|null
     * @throws Tinebase_Exception_NotFound
     */
    public static function getEFileNonDocumentDirNode(Tinebase_Model_Tree_Node $_child)
    {
        while ($_child->parent_id) {
            $_child = Tinebase_FileSystem::getInstance()->getFromStatCache($_child->parent_id);
            if (EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR !== $_child->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
                return $_child;
            }
        }
        return null;
    }



    protected static function getLIVRValidator()
    {
        Validator\LIVR::registerDefaultRules(['if' => function($livr, $ruleBuilders)
        {
            $condition = new \Validator\LIVR($livr['condition']);
            $condition->registerRules($ruleBuilders)->prepare();

            $then = new \Validator\LIVR($livr['then']);
            $then->registerRules($ruleBuilders);

            $else = null;
            if (isset($livr['else'])) {
                $else = new \Validator\LIVR($livr['else']);
                $else->registerRules($ruleBuilders);
            }

            return function ($nestedObject, $params, &$outputValue) use ($condition, $then, $else) {
                $nextValidator = null;
                if (false !== $condition->validate($params)) {
                    $nextValidator = $then;
                } elseif ($else) {
                    $nextValidator = $else;
                }

                if ($nextValidator) {
                    if (false === $nextValidator->validate($params)) {
                        return print_r($nextValidator->getErrors(), true);
                    }
                }
                return null;
            };
        },
            'notEqualToFieldPath' => function ($fieldPath)
            {
                return function ($value, $params) use ($fieldPath) {
                    foreach (explode('.', $fieldPath) as $field) {
                        if (isset($params[$field]))
                            $params = $params[$field];
                        else
                            $params = null;
                    }
                    if ($value === $params)
                        return 'FIELDS_EQUAL';
                };
            }]);

        return new Validator\LIVR(EFile_Config::getNodeLIVR());
    }

    public function hasImplicitRight($_accountId, $_right)
    {
        if (Tinebase_Acl_Rights::RUN === $_right) {
            return Tinebase_Acl_Roles::getInstance()->hasRight(Tinebase_Config::APP_NAME, $_accountId, $_right);
        }
        return false;
    }
}
