<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @property Tinebase_Record_RecordSet      alternative_dates
 */
class Calendar_Model_Poll extends Tinebase_Record_Abstract
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';

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
        'version'           => 1,
        'recordName'        => 'Poll',
        'recordsName'       => 'Polls', // ngettext('Poll', 'Polls', n)
        'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,

        'createModule'      => false,

        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,

        'appName'           => 'Calendar', // _('Calendar')
        'modelName'         => 'Poll',

        'table'             => [
            'name'    => 'cal_polls',
            'options' => ['collate' => 'utf8_general_ci'],
//            'indexes' => array(
//                'container_id' => array(
//                    'columns' => array('container_id')
//                )
//            ),
        ],

        'fields'          => [
            'name' => [
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => true,
                'validators'  => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                'label'       => 'Name', // _('Name')
                'queryFilter' => true
            ],
            'password' => [
                'type' => 'string',
                'nullable' => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters' => ['Zend_Filter_Empty' => null],
                'label' => 'Password' // _('Password')
            ],
            'locked'          => [
                'type'       => 'boolean',
                'nullable'   => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'default'    => 0,
                'label'      => 'Locked', // _('Locked')
            ],
            'closed'          => [
                'type'       => 'boolean',
                'nullable'   => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'default'    => 0,
                'label'      => 'Closed', // _('Closed')
            ],
            'deleted_events'          => [
                'type'       => 'json',
                'nullable'   => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE =>[]],
            ],
            'alternative_dates' => [
                'type'       => 'virtual',
                'config' => [
                    'type' => 'records',
                    'label' => 'Alternative Dates', // _('Alternative Dates')
                    'config' => [
                        'appName' => 'Calendar',
                        'modelName' => 'Event',
                    ],
                ],
            ],
        ]
    ];

    /**
     * returns the (anonymous) poll URL
     *
     * @return string
     */
    public function getPollLink(Calendar_Model_Attender $attendee = null)
    {
        $publicUrl = Tinebase_Core::getUrl() . '/Calendar/view/poll/' . $this->getId();

        return $publicUrl . ($attendee ? (
            '/' . $attendee->getKey() . '/' . $attendee->status_authkey
        ) : '');
    }
}
