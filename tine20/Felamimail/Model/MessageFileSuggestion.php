<?php
/**
 * class to hold MessageFileSuggestion data
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c)2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold MessageFileSuggestion (non-persistent) data
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Model_MessageFileSuggestion extends Tinebase_Record_Abstract
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
    const TYPE_FILE_LOCATION = 'file_location';

    /**
     * type node
     */
    const TYPE_SENDER = 'sender';

    /**
     * type node
     */
    const TYPE_RECIPIENT = 'recipient';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Message File Suggestion', // _('Message File Suggestion') ngettext('Message File Suggestion', 'Message File Suggestions', n)
        'recordsName'       => 'Message File Suggestions', // _('Message File Suggestions')
        'titleProperty'     => 'type',
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => false,
        'hasAttachments'    => false,

        'createModule'      => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Felamimail',
        'modelName'         => 'MessageFileSuggestion',

        'fields'          => array(
            'type' => array(
                'validators' => array(
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    array('InArray', array(self::TYPE_FILE_LOCATION, self::TYPE_RECIPIENT, self::TYPE_SENDER)),
                ),
                'type' => 'string',
                'length'     => 20,
            ),
            // @todo add more properties?
            'record' => array(
                'type' => 'record',
            ),
            // @todo add more properties?
            'model' => array(
                'type' => 'string',
                'length'     => 255,
            ),
        )
    );
}
