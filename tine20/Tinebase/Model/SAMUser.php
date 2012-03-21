<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_SAMUser
 * 
 * @property    string  acctFlags
 * @package     Tinebase
 * @subpackage  Samba
 */
class Tinebase_Model_SAMUser extends Tinebase_Record_Abstract 
{
   
    protected $_identifier = 'sid';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'logonTime',
        'logoffTime',
        'kickoffTime',
        'pwdLastSet',
        'pwdCanChange',
        'pwdMustChange'
    );

    protected $_validators = array(
        'sid'              => array('allowEmpty' => true),
        'primaryGroupSID'  => array('allowEmpty' => true),
        'acctFlags'        => array('allowEmpty' => true),
        'homeDrive'        => array('allowEmpty' => true),
        'homePath'         => array('allowEmpty' => true),
        'profilePath'      => array('allowEmpty' => true),
        'logonScript'      => array('allowEmpty' => true),    
        'logonTime'        => array('allowEmpty' => true),
        'logoffTime'       => array('allowEmpty' => true),
        'kickoffTime'      => array('allowEmpty' => true),
        'pwdLastSet'       => array('allowEmpty' => true),
        'pwdCanChange'     => array('allowEmpty' => true),
        'pwdMustChange'    => array('allowEmpty' => true),
    );
} 
