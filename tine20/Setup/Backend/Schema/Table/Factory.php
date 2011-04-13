<?php
/**
 * backend factory class for the Setup
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
        // legacy for old setup scripts
        if(ucfirst($_type) == 'String') {
            $_type = 'Xml';
        }
        $className = 'Setup_Backend_Schema_Table_' . ucfirst($_type);
        $instance = new $className($_definition);
                      
        return $instance;
    }
    
}    
