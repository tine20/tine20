<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * main authentication class
 */

class Egwbase_Auth
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'Ldap';
    
    /**
     * factory function to return a selected authentication backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($type)
    {
        $className = 'Egwbase_Auth_'.$type;
        $instance = new $className();
        
        //throw new Exception('unknown type');
        
        return $instance;
    }
}
?>