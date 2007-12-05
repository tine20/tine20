<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: $
 */

/**
 * defines the datatype for one application
 */
class Egwbase_Record_Account extends Egwbase_Record_Abstract
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
        'account_id'      => array('Digits', 'presence' => 'required'),
        'account_loginid' => array('presence' => 'required'),
        'account_name'    => array('allowEmpty' => true),
    );

   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'account_id';
    
    public function getGroupMemberships()
    {
        
    }

    public function hasRight($_application, $_right)
    {
        $rights = Egwbase_Acl_Rights::getInstance();
        
        $result = $rights->hasRight($_application, $this->account_id, $_right);
        
        return $result;
    }
}