<?php
/**
 * class for testing auto model creation
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Test data
 * @package Test
 */
class Tinebase_Record_AutoRecord extends Tinebase_Record_Abstract
{
    /**
     * application the record belongs to
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * array with meta information about the model (like models.js)
     * @var array
     */
    protected static $_meta = array(
        'idProperty'        => 'id',
        'titleProperty'     => 'text',
        'recordName'        => 'Record',
        'recordsName'       => 'Records',
        'containerProperty' => NULL,
        'containerName'     => 'Containers',
        'containersName'    => 'Containers',
        'defaultFilter'     => 'text',
        'hasRelations'       => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'useModlog'         => true,
    );

    /**
     * fields for auto bootstrapping
     * @var array
     */
    protected static $_fields = array(
        'id' => array(
            'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
            'label' => null,
        ),
        'text' => array(
            'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
            'label' => 'Text',
        ),
        'date' => array(
            'type' => 'date', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
            'label' => 'Date', // _('Start Date')
        ),
    );
}