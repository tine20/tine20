<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * interface for user ldap plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
interface Tinebase_User_Plugin_Interface
{
    /**
     * the constructor
     *
     * @param  array          $options  options used in connecting, binding, etc.
     */
    public function __construct(array $_options = array());
}  
