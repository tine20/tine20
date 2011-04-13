<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Credential Cache Model
 * 
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_CredentialCache extends Tinebase_Record_Abstract 
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
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     * 
     * @var array list of zend validator
     */
    protected $_validators = array(
        'id'                     => array('Alnum', 'allowEmpty' => true),
        'key'                    => array('Alnum', 'allowEmpty' => true),
        'cache'                  => array('allowEmpty' => true),
        'username'               => array('allowEmpty' => true),
        'password'               => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
        'valid_until'            => array('allowEmpty' => true),
    );
    
    /**
     * name of fields containing datetime or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'valid_until'
    );
    
    /**
     * returns cacheid 
     * 
     * @return array
     */
    public function getCacheId()
    {
        return array(
            'id'    => $this->getId(),
            'key'   => $this->key
        );
    }
    
    /**
     * returns array with record related properties 
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $array = parent::toArray($_recursive);
        
        // remove highly sensitive data to prevent acidential apperance in logs etc.
        unset($array['key']);
        unset($array['username']);
        unset($array['password']);
        
        return $array;
    }
}
