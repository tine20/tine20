<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  LDAP
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        make default quota configurable
 */

/**
 * class Tinebase_Model_EmailUser
 * 
 * - this class contains all email specific user settings like quota, forwards, ...
 * 
 * @package     Tinebase
 * @subpackage  LDAP
 */
class Tinebase_Model_EmailUser extends Tinebase_Record_Abstract 
{
   
    protected $_identifier = 'emailUID';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * validators / fields
     *
     * @var array
     */
    protected $_validators = array(
        'emailUID'          => array('allowEmpty' => true),
        'emailGID'          => array('allowEmpty' => true),
        'emailMailQuota'    => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 500),
        'emailMailSize'     => array('allowEmpty' => true),
        'emailSieveQuota'   => array('allowEmpty' => true),
        'emailSieveSize'    => array('allowEmpty' => true),
        'emailUserId'       => array('allowEmpty' => true),
        'emailLastLogin'    => array('allowEmpty' => true),
        'emailPassword'     => array('allowEmpty' => true),
        'emailForwards'     => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailForwardOnly'  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'emailAliases'      => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailAddress'      => array('allowEmpty' => true),
    // dbmail username (tine username + dbmail domain)
        'emailUsername'     => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'emailLastLogin'
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters['emailForwardOnly'] = new Zend_Filter_Empty(0);
        $this->_filters['emailMailSize'] = new Zend_Filter_Empty(0);
        $this->_filters['emailMailQuota'] = new Zend_Filter_Empty(0);
        $this->_filters['emailForwards'] = new Zend_Filter_Empty(array());
        $this->_filters['emailAliases'] = new Zend_Filter_Empty(array());
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array $_data)
    {
        foreach (array('emailForwards', 'emailAliases') as $arrayField) {
            if (isset($_data[$arrayField]) && ! is_array($_data[$arrayField])) {
                $_data[$arrayField] = explode(',', preg_replace('/ /', '', $_data[$arrayField]));
            }
        }
        
        parent::setFromArray($_data);
    }
    
} 
