<?php
/**
 * class to hold call data
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * class to hold lead data
 * 
 * @package     Crm
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
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'line_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'phone_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'call_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'start'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'connected'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'disconnected'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'duration'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int'),
        'ringing'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int'),
        'direction'             => array(
            Zend_Filter_Input::ALLOW_EMPTY => true, 
            'InArray' => array(self::TYPE_INCOMING, self::TYPE_OUTGOING)),
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
