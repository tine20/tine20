<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement
 */

/**
 * class Tinebase_EmailUser_Imap_Cyrus
 * 
 * Email User Settings Managing for cyrus attributes
 * 
 * @package Tinebase
 * @subpackage User
 */
class Tinebase_EmailUser_Imap_Cyrus extends Tinebase_EmailUser_Abstract
{
    /**
     * get email user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId)
    {
        throw new Tinebase_Exception('not implemented yet');
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     */
    public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
    {
        throw new Tinebase_Exception('not implemented yet');
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
    public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
    {
        throw new Tinebase_Exception('not implemented yet');
    }

    /**
     * delete user by id
     *
     * @param   string         $_userId
     */
    public function deleteUser($_userId)
    {
        throw new Tinebase_Exception('not implemented yet');
    }
    
    /**
     * update/set email user password
     * 
     * @param string $_userId
     * @param string $_password
     * @return void
     */
    public function setPassword($_userId, $_password)
    {
        throw new Tinebase_Exception('not implemented yet');
    }
}
