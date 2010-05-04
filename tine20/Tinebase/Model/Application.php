<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one application
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Application extends Tinebase_Record_Abstract
{
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
    protected $_application = 'Tinebase';
    
	/**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'      => 'StringTrim',
        'version'   => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array();

    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array(
            'id'        => array('allowEmpty' => true),
            'name'      => array('presence' => 'required'),
            'status'    => array('InArray' => array('enabled', 'disabled')),
            'order'     => array('Digits', 'presence' => 'required'),
            'tables'    => array('allowEmpty' => true),
            'version'   => array('presence' => 'required')
        );
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Tinebase_Model_Application to an accountid
     *
     * @param   int|string|Tinebase_Model_Application $_accountId the accountid to convert
     * @return  int
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function convertApplicationIdToInt($_applicationId)
    {
        if($_applicationId instanceof Tinebase_Model_Application) {
            if(empty($_applicationId->id)) {
                throw new Tinebase_Exception_InvalidArgument('No application id set.');
            }
            $applicationId = $_applicationId->id;
        } elseif (is_string($_applicationId) && strlen($_applicationId) != 40) {
            $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_applicationId)->getId();
        } else {
            $applicationId = $_applicationId;
        }
        
        return $applicationId;
    }
        
    /**
     * returns applicationname
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }    
    
    /**
     * return the major version of the appliaction
     *
     * @return  int the major version
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getMajorVersion()
    {
        if(empty($this->version)) {
            throw new Tinebase_Exception_InvalidArgument('No version set.');
        }
        
        list($majorVersion, $minorVersion) = explode('.', $this->version);
        
        return $majorVersion;
    }
}