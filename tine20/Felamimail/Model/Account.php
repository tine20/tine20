<?php
/**
 * class to hold Account data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        save encrypted password
 * @todo        use enum/array for tls (and more fields?)
 * @todo        add user/pass for smtp or use the imap credentials?
 */

/**
 * class to hold Account data
 * 
 * @package     Felamimail
 */
class Felamimail_Model_Account extends Tinebase_Record_Abstract
{  
    /**
     * default account id
     *
     */
    const DEFAULT_ACCOUNT_ID = 'default';
    
    /**
     * key in $_validators/$_properties array for the field which 
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
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'user'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => ''),
        'port'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 143),
        'secure_connection'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'tls'),
        'signature'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // smtp config
        'smtp_port'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 25),
        'smtp_hostname'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'smtp_auth'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'login'),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
     * get imap config array
     *
     * @return array
     * 
     * @todo decrypt pwd here?
     */
    public function getImapConfig()
    {
        $imapConfigFields = array('host', 'port', 'user', 'password');
        
        $result = array();
        foreach ($imapConfigFields as $field) {
            $result[$field] = $this->{$field};
        }
        return $result;
    }
    
    /**
     * get smtp config
     *
     * @return array
     * 
     * @todo finish
     */
    public function getSmtpConfig()
    {
        $result = array(
            'host'  => $this->smtp_hostname,
        );
        
        return $result;
    }

    /**
     * to array
     *
     * @param boolean $_recursive
     * 
     * @todo don't show password? / only encrypted?
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);
        
        //$result['password'] = '********';
        
        return $result;
    }
}
