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
 * defines the datatype for one container
 * 
 * @package     Tinebase
 * @subpackage  Record
 * 
 * NOTE: container class is in the transition from int based grants to string based
 *       grants! In the next refactoring step of container class, int based grants 
 *       will be replaced
 */
class Tinebase_Model_Container extends Tinebase_Record_Abstract
{
    /**
     * constant for no grants
     *
     */
    const GRANT_NONE = 0;

    /**
     * constant for read grant
     *
     */
    const GRANT_READ = 1;
    const READGRANT = 'readGrant';

    /**
     * constant for add grant
     *
     */
    const GRANT_ADD = 2;
    const ADDGRANT = 'addGrant';

    /**
     * constant for edit grant
     *
     */
    const GRANT_EDIT = 4;
    const EDITGRANT = 'editGrant';

    /**
     * constant for delete grant
     *
     */
    const GRANT_DELETE = 8;
    const DELETEGRANT = 'deleteGrant';

    /**
     * constant for admin grant
     *
     */
    const GRANT_ADMIN = 16;
    const ADMINGRANT = 'adminGrant';

    /**
     * constant for all grants
     *
     */
    const GRANT_ANY = 31;
    
    /**
     * type for internal contaier
     * 
     * for example the internal addressbook
     *
     */
    const TYPE_INTERNAL = 'internal';
    
    /**
     * type for personal containers
     *
     */
    const TYPE_PERSONAL = 'personal';
    
    /**
     * type for shared container
     *
     */
    const TYPE_SHARED = 'shared';
    
    /**
     * maps grant constants to names
     *
     * @var array
     */
    public static $GRANTNAMEMAP = array(
        self::GRANT_READ    => self::READGRANT,
        self::GRANT_ADD     => self::ADDGRANT,
        self::GRANT_EDIT    => self::EDITGRANT,
        self::GRANT_DELETE  => self::DELETEGRANT,
        self::GRANT_ADMIN   => self::ADMINGRANT
    );
    
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
        'type'              => array('InArray' => array(self::TYPE_INTERNAL, self::TYPE_PERSONAL, self::TYPE_SHARED)),
        'backend'           => array('presence' => 'required'),
        'application_id'    => array('Alnum', 'presence' => 'required'),
        'account_grants'    => array('allowEmpty' => true, /*'presence' => 'required'*/)
    );
    
    /**
     * converts a int, string or Tinebase_Model_Container to a containerid
     *
     * @param   int|string|Tinebase_Model_Container $_containerId the containerid to convert
     * @return  int
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function convertContainerIdToInt($_containerId)
    {
        if($_containerId instanceof Tinebase_Model_Container) {
            if($_containerId->getId() === NULL) {
                throw new Tinebase_Exception_InvalidArgument('No container id set.');
            }
            $id = (int) $_containerId->getId();
        } else {
            $id = (int) $_containerId;
        }
        
        if($id === 0) {
            throw new Tinebase_Exception_InvalidArgument('Container id can not be 0.');
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