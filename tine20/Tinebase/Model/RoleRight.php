<?php
/**
 * model to handle role rights
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for role rights
 * 
 * @package     Tinebase
 * @subpackage  Acl
 *  */
class Tinebase_Model_RoleRight extends Tinebase_Record_Abstract
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
        'recordName'        => 'RoleRight',
        'recordsName'       => 'RoleRights', // ngettext('RoleRight', 'RoleRights', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,

        'titleProperty'     => 'id',
        'appName'           => 'Tinebase',
        'modelName'         => 'RoleRight',

        'fields' => array(
            'role_id'           => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'integer',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'application_id'    => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'right'             => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
        )
    );
}
