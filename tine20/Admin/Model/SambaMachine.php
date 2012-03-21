<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Model of a samba machine
 *
 * @package    Admin
 * @subpackage Samba
 */
class Admin_Model_SambaMachine extends Tinebase_Record_Abstract
{
    protected $_identifier = 'accountId';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Admin';
    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        //'logonTime',
        //'logoffTime',
        //'kickoffTime',
        //'pwdLastSet',
        //'pwdCanChange',
        'pwdMustChange'
    );

    protected $_validators = array(
        'accountId'             => array('allowEmpty' => true),
        'accountLoginName'      => array('presence' => 'required'),
        'accountLastName'       => array('allowEmpty' => true),
        'accountFullName'       => array('allowEmpty' => true),
        'accountDisplayName'    => array('allowEmpty' => true),
        'accountPrimaryGroup'   => array('allowEmpty' => true),
        'accountHomeDirectory'  => array('allowEmpty' => true),
        'accountLoginShell'     => array('allowEmpty' => true),
        'sid'                   => array('allowEmpty' => true),
        'primaryGroupSID'       => array('allowEmpty' => true),
        'acctFlags'             => array('allowEmpty' => true),
        //'homeDrive'             => array('allowEmpty' => true),
        //'homePath'              => array('allowEmpty' => true),
        //'profilePath'           => array('allowEmpty' => true),
        //'logonScript'           => array('allowEmpty' => true),    
        //'logonTime'             => array('allowEmpty' => true),
        //'logoffTime'            => array('allowEmpty' => true),
        //'kickoffTime'           => array('allowEmpty' => true),
        //'pwdLastSet'            => array('allowEmpty' => true),
        //'pwdCanChange'          => array('allowEmpty' => true),
        'pwdMustChange'         => array('allowEmpty' => true),
    );
}
