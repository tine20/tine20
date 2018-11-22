<?php
/**
 * class to hold MessageFileLocation data
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c)2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold MessageFileLocation data
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Model_MessageFileLocation extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * type attachment
     */
    const TYPE_ATTACHMENT = 'attachment';

    /**
     * type node
     */
    const TYPE_NODE = 'node';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'           => 1,
        'recordName'        => 'Message File Location', // _('Message File Location') ngettext('Message File Location', 'Message File Locations', n)
        'recordsName'       => 'Message File Locations', // _('Message File Locations')
        'titleProperty'     => 'message_id',
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,

        'createModule'      => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Felamimail',
        'modelName'         => 'MessageFileLocation',

        'table'             => array(
            'name'    => 'felamimail_message_filelocation',
            'indexes' => array(
                'message_id_hash' => array(
                    'columns' => array('message_id_hash')
                ),
                'model' => array(
                    'columns' => array('model')
                ),
                'record_id' => array(
                    'columns' => array('record_id')
                ),
            ),
            'uniqueConstraints' => [
                'message_model_record' => [
                    'columns' => ['message_id_hash', 'model', 'record_id']
                ]
            ],
        ),

        'fields'          => array(
            // message-id Header - we can't use "our" message id because the cache is temporary
            // hashed - for the index
            'message_id_hash' => array(
                'type'       => 'string',
                'length'     => 40,
                'nullable'   => false,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Message ID Hash', // _('Message ID Hash')
            ),
            // message-id Header - we can't use "our" message id because the cache is temporary
            'message_id' => array(
                'type'       => 'text',
                'nullable'   => false,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Message ID', // _('Message ID')
            ),
            // see https://stackoverflow.com/questions/30079128/maximum-internet-email-message-id-length
            'model' => array(
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => false,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
                'label'      => 'Model', // _('Model')
            ),
            'record_id' => array(
                'type'       => 'string',
                'length'     => 40,
                'nullable'   => false,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
                'label'      => 'Record ID', // _('Record ID')
            ),
            'record_title' => array(
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Record Title', // _('Record Title')
            ),
            'type' => array(
                'validators' => array(
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    array('InArray', array(self::TYPE_ATTACHMENT, self::TYPE_NODE)),
                ),
                'label' => 'Type', // _('Type')
                'type' => 'string',
                'length'     => 20,
                'nullable'   => false,
                'default' => self::TYPE_NODE
            ),
        )
    );
}
