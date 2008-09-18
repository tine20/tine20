 <?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * cli server for addressbook
 *
 * This class handles cli requests for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Addressbook';

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'import' => array(
            'description'   => 'Import new contacts into the addressbook.',
            'params'        => array(
                '--filename <string>'   => 'Filename of import file',
                '--format <string>'     => 'Supported fromats (only csv at the moment',
                '--config <string>'     => 'Mapping config file (use mapping.inc.php as default)',
            )
        )
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
    
    /**
     * import contacts
     *
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo implement
     */
    public function import($_opts)
    {
        echo "not implemented yet.\n";
    }
}
