<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 *
 * @package     Tinebase
 * @subpackage  Model
 * @property    string                      contenttype
 * @property    Tinebase_DateTime           creation_time
 * @property    string                      hash
 * @property    string                      indexed_hash
 * @property    string                      name
 * @property    Tinebase_DateTime           last_modified_time
 * @property    string                      object_id
 * @property    string                      parent_id
 * @property    string                      size
 * @property    string                      revision_size
 * @property    string                      type
 * @property    string                      revision
 * @property    string                      available_revisions
 * @property    string                      description
 * @property    string                      acl_node
 * @property    array                       revisionProps
 * @property    array                       notificationProps
 * @property    string                      preview_count
 * @property    integer                     quota
 * @property    Tinebase_Record_RecordSet   grants
 * @property    string                      pin_protected_node
 * @property    string                      path
 * @property    Tinebase_DateTime           lastavscan_time
 * @property    boolean                     is_quarantined
 */
class Tinebase_Model_Tree_Node extends Tinebase_Record_Abstract
{
    const XPROPS_REVISION = 'revisionProps';
    const XPROPS_REVISION_NODE_ID = 'nodeId';
    const XPROPS_REVISION_ON = 'keep';
    const XPROPS_REVISION_NUM = 'keepNum';
    const XPROPS_REVISION_MONTH = 'keepMonth';

    /**
     * {"notificationProps":[{"active":true,....},{"active":true....},{....}]}
     */
    const XPROPS_NOTIFICATION = 'notificationProps';
    const XPROPS_NOTIFICATION_ACTIVE = 'active';
    const XPROPS_NOTIFICATION_SUMMARY = 'summary';
    const XPROPS_NOTIFICATION_ACCOUNT_ID = 'accountId';
    const XPROPS_NOTIFICATION_ACCOUNT_TYPE = 'accountType';
    
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

    /**
     * name of fields that should be omitted from modlog
     *
     * @var array list of modlog omit fields
     */
    protected $_modlogOmitFields = ['revision', 'contenttype', 'description', 'revision_size', 'indexed_hash', 'hash',
        'size', 'preview_count', 'available_revisions', 'path', 'isIndexed', 'lastavscan_time', 'is_quarantined'];

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('created_by', 'last_modified_by')
    );

    /**
     * list of zend validator
     *
     * these validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        // tine 2.0 generic fields
        'id'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => null),
        'created_by'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // model specific fields
        'parent_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => null),
        'object_id'         => array('presence' => 'required'),
        'revisionProps'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notificationProps' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // contains id of node with acl info
        'acl_node'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'              => array('presence' => 'required'),
        'islink'            => array(
            Zend_Filter_Input::DEFAULT_VALUE => '0',
            array('InArray', array(true, false))
        ),
        'quota'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        'relations' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tags' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields' => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        // fields from filemanager_objects table (ro)
        'type'           => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(Tinebase_Model_Tree_FileObject::TYPE_FILE,
                Tinebase_Model_Tree_FileObject::TYPE_FOLDER, Tinebase_Model_Tree_FileObject::TYPE_PREVIEW)),
        ),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contenttype'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'revision'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'available_revisions'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'hash'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'indexed_hash'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'isIndexed'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'size'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'revision_size'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'preview_count'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Digits',
            Zend_Filter_Input::DEFAULT_VALUE => 0),
        'pin_protected_node'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lastavscan_time'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_quarantined'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        // not persistent
        'container_name' => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        // this is needed should be sent by / delivered to client (not persistent in db atm)
        'path'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_grants' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tempFile'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'stream'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // acl grants
        'grants'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
    * overwrite constructor to add more filters
    *
    * @param mixed $_data
    * @param bool $_bypassFilters
    * @param mixed $_convertDates
    */
    public function __construct($_data = null, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters['size'] = new Zend_Filter_Empty(0);
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    public function runConvertToRecord()
    {
        if (isset($this->_properties['deleted_time']) && $this->_properties['deleted_time'] == '1970-01-01 00:00:00') {
            unset($this->_properties['deleted_time']);
        }
        if (isset($this->_properties['available_revisions']) && is_string($this->_properties['available_revisions'])) {
            $this->_properties['available_revisions'] = explode(',', ltrim(
                rtrim($this->_properties['available_revisions'], '}'), '{'));
        }

        parent::runConvertToRecord();
    }

    public function runConvertToData()
    {
        if (array_key_exists('deleted_time', $this->_properties) && null === $this->_properties['deleted_time']) {
            $this->_properties['deleted_time'] = '1970-01-01 00:00:00';
        }
        if (isset($this->_properties[self::XPROPS_REVISION]) && is_array($this->_properties[self::XPROPS_REVISION])) {
            if (count($this->_properties[self::XPROPS_REVISION]) > 0) {
                $this->_properties[self::XPROPS_REVISION] = json_encode($this->_properties[self::XPROPS_REVISION]);
            } else {
                $this->_properties[self::XPROPS_REVISION] = null;
            }
        }

        if (isset($this->_properties[self::XPROPS_NOTIFICATION]) &&
                is_array($this->_properties[self::XPROPS_NOTIFICATION])) {
            if (count($this->_properties[self::XPROPS_NOTIFICATION]) > 0) {
                $this->_properties[self::XPROPS_NOTIFICATION] =
                    json_encode($this->_properties[self::XPROPS_NOTIFICATION]);
            } else {
                $this->_properties[self::XPROPS_NOTIFICATION] = null;
            }
        }

        parent::runConvertToData();
    }

    /**
     * returns real filesystem path
     *
     * @param string $baseDir
     * @throws Tinebase_Exception_NotFound
     * @return string
     */
    public function getFilesystemPath($baseDir = null)
    {
        if (empty($this->hash)) {
            throw new Tinebase_Exception_NotFound('file object hash is missing');
        }

        if ($baseDir === null) {
            $baseDir = Tinebase_Core::getConfig()->filesdir;
        }

        return $baseDir . DIRECTORY_SEPARATOR . substr($this->hash, 0, 3) . DIRECTORY_SEPARATOR .
            substr($this->hash, 3);
    }

    public function isValid($_throwExceptionOnInvalidData = false)
    {
        if ($this->_isValidated === true) {
            return true;
        }

        $invalid = array();
        if (!empty($this->xprops(static::XPROPS_REVISION))) {
            if (count($this->revisionProps) > 4 || !isset($this->revisionProps[static::XPROPS_REVISION_NODE_ID])
                    || !isset($this->revisionProps[static::XPROPS_REVISION_MONTH]) ||
                    !isset($this->revisionProps[static::XPROPS_REVISION_ON]) ||
                    !isset($this->revisionProps[static::XPROPS_REVISION_NUM])) {
                $invalid[] = 'revisionProps';
            }
        }
        if (!empty($this->xprops(static::XPROPS_NOTIFICATION))) {
            foreach ($this->notificationProps as $val) {
                foreach ($val as $key => $val1) {
                    if (!in_array($key, array(
                            static::XPROPS_NOTIFICATION_ACCOUNT_ID,
                            static::XPROPS_NOTIFICATION_ACCOUNT_TYPE,
                            static::XPROPS_NOTIFICATION_ACTIVE,
                            static::XPROPS_NOTIFICATION_SUMMARY))) {
                        $invalid[] = 'notificationProps';
                        break 2;
                    }
                }
            }
        }

        if (!empty($invalid) && $_throwExceptionOnInvalidData) {
            $e = new Tinebase_Exception_Record_Validation('Some fields ' . implode(',', $invalid)
                . ' have invalid content');

            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " "
                . $e->getMessage()
                . print_r($this->_validationErrors, true));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Record: ' . print_r($this->toArray(), true));

            throw $e;
        }

        return parent::isValid($_throwExceptionOnInvalidData);
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        if ($this->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
            return true;
        }
        if ($this->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            if (strlen($this->name) === 3 &&
                    Tinebase_FileSystem_Previews::getInstance()->getBasePathNode()->getId() === $this->parent_id) {
                return false;
            }
            if (strlen($this->name) === 37 && null !== $this->parent_id &&
                    Tinebase_FileSystem_Previews::getInstance()->getBasePathNode()->getId() ===
                    Tinebase_FileSystem::getInstance()->get($this->parent_id)->parent_id) {
                return false;
            }
            return true;
        }
        return false;
    }
}
