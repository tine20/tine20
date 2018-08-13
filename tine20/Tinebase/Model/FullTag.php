<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * defines the datatype for one full (including all rights and contexts) tag
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Model_FullTag extends Tinebase_Model_Tag
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
    protected static $_modelConfiguration = [
        'recordName'        => 'Tag',
        'recordsName'       => 'Tags', // ngettext('Tag', 'Tags', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'Tag',

        'filterModel'       => [],

        'fields'            => [
            'type'                          => [
                'type'                          => 'string',
                'validators'                    => [
                    'inArray' => [
                        self::TYPE_PERSONAL,
                        self::TYPE_SHARED,
                    ]
                ],
            ],
            'owner'                         => [
                //'type'                          => 'record',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'name'                          => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'description'                   => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'color'                         => [
                'type'                          => 'string',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    ['regex', '/^#[0-9a-fA-F]{6}$/'],
                ],
            ],
            'occurrence'                    => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'selection_occurrence'          => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'account_grants'                => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'rights'                        => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'contexts'                      => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ],
    ];
}
