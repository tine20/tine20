<?php
/**
 * Tine 2.0
 * @package     Projects
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli frontend for projects
 *
 * This class handles cli requests for projects
 *
 * @package     Projects
 * @subpackage  Frontend
 */
class Projects_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Projects';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        /*
        'functionName' => array(
            'description'   => 'function description',
            'params'        => array()
            )
        ),
        */
    );
    
    /**
     * echos usage information
     *
     */
    public function getHelp()
    {
        foreach ($this->_help as $functionHelp) {
            echo $functionHelp['description']."\n";
            echo "parameters:\n";
            foreach ($functionHelp['params'] as $param => $description) {
                echo "$param \t $description \n";
            }
        }
    }
}
