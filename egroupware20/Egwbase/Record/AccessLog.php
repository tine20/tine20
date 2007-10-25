<?php
/**
 * defines the datatype for one access log entry
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Record_AccessLog extends Egwbase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'      => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'sessionid'  => array('presence' => 'required'),
        'loginid'    => array('presence' => 'required'),
        'ip'         => array('presence' => 'required'),
        'li'         => array('presence' => 'required'),
        'lo'         => array('presence' => 'required'),
        'log_id'     => array('presence' => 'required'),
        'result'     => array('presence' => 'required'),
        'account_id' => array('presence' => 'required')
    );
}