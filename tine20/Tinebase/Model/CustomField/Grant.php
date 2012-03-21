<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_CustomField_Grant
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 */
class Tinebase_Model_CustomField_Grant extends Tinebase_Record_Abstract 
{
    /**
     * grant to write custom field
     */
    const GRANT_READ = 'readGrant';
    
    /**
     * grant to write custom field
     */
    const GRANT_WRITE = 'writeGrant';
    
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
     * Defintion of properties
     * 
     * @var array list of zend validator
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'customfield_id'    => array('presence' => 'required'),
        'account_id'        => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => '0'),
        'account_type'      => array(
            'presence' => 'required',
            array('InArray', array(
                Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            )),
        ),
        'account_grant'     => array('presence' => 'required'),
    );
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_WRITE,
        );
    
        return $allGrants;
    }
}
