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
 
 * @package     Setup
 */
class Setup_Backend_Schema_Table_Factory
{
  /**
     * factory function to return a selected 
     *
     * @param string $type
     * @return object
     */
    static public function factory($_type, $_definition)
    {
        $className = 'Setup_Backend_Schema_Table_' . ucfirst($_type);
        $instance = new $className($_definition);
                      
        return $instance;
    }
    
}    
