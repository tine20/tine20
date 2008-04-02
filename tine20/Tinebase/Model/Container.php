<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one container
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Container extends Tinebase_Record_Abstract
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
        'name'              => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('Digits', 'allowEmpty' => true),
        'name'              => array('presence' => 'required'),
        'type'              => array('InArray' => array(Tinebase_Container::TYPE_INTERNAL, Tinebase_Container::TYPE_PERSONAL, Tinebase_Container::TYPE_SHARED)),
        'backend'           => array('presence' => 'required'),
        'application_id'    => array('Digits', 'presence' => 'required'),
        'account_grants'    => array('Digits', 'allowEmpty' => true, /*'presence' => 'required'*/)
    );
    
    /**
     * converts a int, string or Tinebase_Model_Container to a containerid
     *
     * @param int|string|Tinebase_Model_Container $_containerId the containerid to convert
     * @return int
     */
    static public function convertContainerIdToInt($_containerId)
    {
        if($_containerId instanceof Tinebase_Model_Container) {
            if($_containerId->getId() === NULL) {
                throw new Exception('no container id set');
            }
            $id = (int) $_containerId->getId();
        } else {
            $id = (int) $_containerId;
        }
        
        if($id === 0) {
            throw new Exception('container id can not be 0');
        }
        
        return $id;
    }
    
    /**
     * returns containername
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}