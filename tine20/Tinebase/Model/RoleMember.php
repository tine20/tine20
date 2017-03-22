<?php
/**
 * model to handle role members
 *
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines the data type for role members
 *
 * @package     Tinebase
 * @subpackage  Acl
 *  */
class Tinebase_Model_RoleMember extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'RoleMember',
        'recordsName'       => 'RoleMembers', // ngettext('RoleMember', 'RoleMembers', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,

        'titleProperty'     => 'id',
        'appName'           => 'Tinebase',
        'modelName'         => 'RoleMember',

        'fields' => array(
            'role_id'           => array(
                'label'             => 'role_id', //_('role_id')
                'type'              => 'integer',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'account_type'      => array(
                'label'             => 'account_type', //_('account_type')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'account_id'        => array(
                'label'             => 'account_id', //_('account_id')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
        )
    );
}
