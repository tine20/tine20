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
 */

/**
 * defines Timeaccount grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 *  */
class Timetracker_Model_TimeaccountGrant extends Tinebase_Record_Abstract
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
     * @deprecated ?
     */
    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = NULL)
    {
        /*
        $this->_validators = array(
            'id'          => array('Alnum', 'allowEmpty' => TRUE),
            'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => 0),
            'account_type' => array('presence' => 'required', 'InArray' => array('anyone','user','group')),
            //'account_name' => array('allowEmpty' => TRUE),
            'readGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'addGrant'    => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'editGrant'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'deleteGrant' => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'adminGrant'  => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            )
        );
        */
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
    
}
