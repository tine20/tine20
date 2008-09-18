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
     * import config filename
     *
     * @var string
     */
    protected $_configFilename = 'importconfig.inc.php';

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'import' => array(
            'description'   => 'Import new contacts into the addressbook.',
            'params'        => array(
                'filenames'   => 'Filename(s) of import file(s) [required]',
                //'format'     => 'Import file format (default: csv) [optional]',
                //'config'     => 'Mapping config file (default: importconfig.inc.php) [optional]',
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
     */
    public function import($_opts)
    {
        $args = $_opts->getRemainingArgs();
            
        // get csv importer
        $importer = Addressbook_Import_Factory::factory('Csv');
        
        // get mapping and container (from config file)
        if(file_exists($this->_configFilename)) {
            $config = new Zend_Config(require $this->_configFilename);
        } else {
            echo "Import config file not found.\n";
        }
        
        // loop files in argv
        foreach ($args as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $records = $importer->read($filename, $config->mapping->toArray());
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                continue;
            }
            
            // import (check if dry run)
            if (!$_opts->d) {
                if ($_opts->v) {
                    echo "importing ". count($records) ." records...";
                }
                $importedRecords = $importer->import($records, $config->containerId);
                if ($_opts->v) {
                    echo "done.\n";
                }
                if ($_opts->v) {
                    foreach ($importedRecords as $contact) {
                        echo "Imported contact: " . $contact->n_fn ."\n";
                    }   
                }
            } else {
                print_r($records->toArray());
            }        
        }
    }
}
