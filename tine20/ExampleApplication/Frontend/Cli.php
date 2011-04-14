<?php
/**
 * Tine 2.0
 * @package     ExampleApplication
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server for timetracker
 *
 * This class handles cli requests for the timetracker
 *
 * @package     ExampleApplication
 * @subpackage  Frontend
 */
class ExampleApplication_Frontend_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'ExampleApplication';
    
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
