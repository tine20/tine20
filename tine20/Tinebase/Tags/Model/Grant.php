<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for a set of grants for one tag and one account
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Tags_Model_Grant extends Tinebase_Record_Abstract
{
    /**
     * Grant to view/see/read the tag
     */
    const GRANT_VIEW = 'view';
    /**
     * Grant to attach the tag to a record
     */
    const GRANT_ATTACH = 'attach';
    /**
     * Grant to detach the tag from a record
     */
    const GRANT_DETACH = 'detach';
    /**
     * Implies all grants above
     */
    const GRANT_ALL = NULL;
    
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
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'           => array('Alnum', 'allowEmpty' => true),
        'tag_id'       => array('Alnum', 'presence' => 'required', 'allowEmpty' => false),
        'account_type' => array('InArray' => array('user', 'group', 'anyone'), 'presence' => 'required', 'allowEmpty' => false),
        'account_id'   => array('Alnum', 'presence' => 'required', 'allowEmpty' => false),
        'grant_view'   => array('presence' => 'required', 'default' => false, 'InArray' => array(true, false), 'allowEmpty' => true),
        'grant_attach' => array('presence' => 'required', 'default' => false, 'InArray' => array(true, false), 'allowEmpty' => true),
        'grant_detach' => array('presence' => 'required', 'default' => false, 'InArray' => array(true, false), 'allowEmpty' => true),
    );
}