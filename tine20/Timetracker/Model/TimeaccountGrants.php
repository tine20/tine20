<?php
/**
 * class to handle grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        write tests for this
 * @todo        add phpdoc
 * @todo        do we need the constants?
 * @todo        extend Tinebase_Model_Grants?
 */

/**
 * defines Timeaccount grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 *  */
class Timetracker_Model_TimeaccountGrants extends Tinebase_Record_Abstract
{
    /**
     * constant for book own TS grant (GRANT_READ)
     *
     */
    const BOOK_OWN = 1;

    /**
     * constant for view all TS (GRANT_ADD)
     *
     */
    const VIEW_ALL = 2;

    /**
     * constant for book TS for all users (GRANT_EDIT)
     *
     */
    const BOOK_ALL = 4;

    /**
     * constant for managa clearing and read complete TA (GRANT_DELETE)
     *
     */
    const MANAGE_CLEARING = 8;

    /**
     * constant for manage all / admin grant (GRANT_ADMIN)
     *
     */
    const MANAGE_ALL = 16;

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
    protected $_application = 'Timetracker';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array();
    
    /**
     * overwrite constructor
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     *
     */
    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = NULL)
    {
        $this->_validators = array(
            'id'          => array('Alnum', 'allowEmpty' => TRUE),
            'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => 0),
            'account_type' => array('presence' => 'required', 'InArray' => array('anyone','user','group')),
        
            'book_own'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'view_all'    => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'book_all'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'manage_clearing' => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'manage_all'  => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            )
        );
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * wrapper for Tinebase_Container::hasGrant()
     *
     * @param Timetracker_Model_Timeaccount $_timeaccount
     * @param integer $_grant
     * @return boolean
     */
    public static function hasGrant($_timeaccount, $_grant)
    {
        return Tinebase_Container::getInstance()->hasGrant(
            Tinebase_Core::getUser()->getId(), 
            $_timeaccount->container_id, 
            $_grant
        );
    }
    
    public static function getGrants($_timeaccount)
    {
        //-- use this?    
    }
    
    /**
     * get timeaccounts by grant
     *
     * @param integer $_grant
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public static function getTimeaccountsByAcl($_grant, $_onlyIds = FALSE)
    {
        $containerIds = Tinebase_Container::getInstance()->getContainerByACL(
            Tinebase_Core::getUser()->getId(),
            $this->_application,
            $_grant,
            TRUE
        );
        
        $filter = new Timetracker_Model_TimeaccountFilter(array(
            'container' => $containerIds
        ), FALSE);
        
        $backend = new Timetracker_Backend_Timeaccount();
        $result = $backend->search($filter, new Tinebase_Model_Pagination());
        
        if ($_onlyIds) {
            return $result->getArrayOfIds();
        } else {
            return $result;
        }
    }
    
    /**
     * set timeaccount grants
     *
     * @param Timetracker_Model_Timeaccount $_timeaccount
     * @param Tinebase_Record_RecordSet $_grants
     * @param boolean $_ignoreACL
     */
    public static function setTimeaccountGrants($_timeaccount, Tinebase_Record_RecordSet $_grants, $_ignoreACL = FALSE)
    {
        // map Timetracker_Model_TimeaccountGrants to Tinebase_Model_Grants
        $grants = self::doMapping($grant);
        
        Tinebase_Container::getInstance()->setGrants($_timeaccount->container_id, $_grants, $_ignoreACL);
    }
    
    /**
     * map to Tinenbase_Model_Grants
     *
     * @param Tinebase_Record_RecordSet $_grants
     * @return Tinebase_Record_RecordSet
     */
    public static function doMapping(Tinebase_Record_RecordSet $_grants)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        foreach ($_grants as $grant) {
            $result->addRecord(new Tinebase_Model_Grants(array(
                'account_id'    => $grant->account_id,
                'account_type'  => $grant->account_type,
                'readGrant'     => $grant->book_own,
                'addGrant'      => $grant->view_all,
                'editGrant'     => $grant->book_all,
                'deleteGrant'   => $grant->manage_clearing,
                'adminGrant'    => $grant->manage_all
            )));
        }
        return $result;
    }
}
