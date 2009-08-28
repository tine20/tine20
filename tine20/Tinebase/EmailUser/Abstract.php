<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail attributes
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
abstract class Tinebase_EmailUser_Abstract
{
    /**
     * get email user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    abstract public function getUserById($_userId);

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     */
	abstract public function addUser($_user, Tinebase_Model_EmailUser $_emailUser);
	
	/**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
	abstract public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser);

    /**
     * delete user by id
     *
     * @param   string         $_userId
     */
    abstract public function deleteUser($_userId);
	
    /**
     * update/set email user password
     * 
     * @param string $_userId
     * @param string $_password
     * @return void
     */
    abstract public function setPassword($_userId, $_password);
    
}  
