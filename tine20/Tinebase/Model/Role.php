<?php
/**
 * model to handle roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * defines the datatype for roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 *
 * @property string                     $id
 * @property string                     $name
 * @property Tinebase_Record_RecordSet  $members
 * @property Tinebase_Record_RecordSet  $rights
 *
 */
class Tinebase_Model_Role extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static $_isReplicable = true;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Role',
        'recordsName'       => 'Roles', // ngettext('Role', 'Roles', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,

        'titleProperty'     => 'name',
        'appName'           => 'Tinebase',
        'modelName'         => 'Role',

        'fields' => array(
            'name'              => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'inputFilters'      => array('Zend_Filter_StringTrim' => NULL),
            ),
            'description'       => array(
                'label'             => 'Description', //_('Description')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
            ),
            'rights'            => array(
                'label'             => 'Rights', // _('Rights')
                'type'              => 'records', // be careful: records type has no automatic filter definition!
                'config'            => array(
                    'appName'               => 'Tinebase',
                    'modelName'             => 'RoleRight',
                    'controllerClassName'   => 'Tinebase_RoleRight',
                    'refIdField'            => 'role_id',
                    'dependentRecords'      => TRUE,
                ),
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
            ),
            'members'           => array(
                'label'             => 'Members', // _('Members')
                'type'              => 'records', // be careful: records type has no automatic filter definition!
                'config'            => array(
                    'appName'               => 'Tinebase',
                    'modelName'             => 'RoleMember',
                    'controllerClassName'   => 'Tinebase_RoleMember',
                    'refIdField'            => 'role_id',
                    'dependentRecords'      => TRUE,
                ),
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
            ),
        )
    );
    
    /**
     * returns role name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    public static function setIsReplicable($bool = true)
    {
        static::$_isReplicable = (bool)$bool;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return static::$_isReplicable;
    }

    /**
     * converts a int, string or Tinebase_Model_Role to a roleid
     *
     * @param   int|string|Tinebase_Model_Role $_roleId the roleid to convert
     * @return  string
     * @throws  Tinebase_Exception_InvalidArgument
     *
     * @todo rename this function because we now have string ids
     */
    static public function convertRoleIdToInt($_roleId)
    {
        return self::convertId($_roleId, 'Tinebase_Model_Role');
    }
}
