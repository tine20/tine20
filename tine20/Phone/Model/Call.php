<?php
/**
 * class to hold call data
 * 
 * @package     Phone
 * @subpackage    Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold lead data
 * 
 * @package     Phone
 * @subpackage    Model
 */
class Phone_Model_Call extends Tinebase_Record_Abstract
{
    /**
     * Type of call
     */
    const TYPE_INCOMING = 'in';
    
    /**
     * Type of call
     */
    const TYPE_OUTGOING = 'out';
    
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
    protected $_application = 'Phone';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        /*
        'id'            => 'Digits',
        'lead_name'     => 'StringTrim',
        'probability'   => 'Digits'
        */
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'line_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'phone_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'callerid'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'start'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'connected'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'disconnected'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'duration'              => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            'Int', 
            Zend_Filter_Input::DEFAULT_VALUE => 0
        ),
        'ringing'               => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            'Int', 
            Zend_Filter_Input::DEFAULT_VALUE => 0
        ),
        'direction'             => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            array('InArray', array(self::TYPE_INCOMING, self::TYPE_OUTGOING)),
        ),
        'source'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'destination'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'start',
        'connected',
        'disconnected',
    );
}
