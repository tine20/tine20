<?php
/**
 * backend factory class for the Setup
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Factory.php 1340 2008-03-25 20:08:53Z lkneschke $ *
 */

/**
 * backend factory class for the Setup
 * 
 * an instance of the Setup backendclass should be created using this class
 * 
 * $contacts = Setup_Backend::factory(Setup_Backend::$type);
 * 
 * currently implemented backend classes: Setup_Backend::MySql
 * 
 * @package     Setup
 */
class Setup_Backend_Factory
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Mysql';
    
  /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($_type)
    {
        switch($_type) {
            case self::SQL:
                $className = 'Setup_Backend_' . ucfirst($_type);
                $instance = new $className();
                break;
                
            default:
                throw new Exception('unknown type');
        }

        return $instance;
    }
    
}    
