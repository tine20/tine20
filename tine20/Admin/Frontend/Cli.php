 <?php
/**
 * Tine 2.0
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        create/extend abstract Cli (Tinebase_Application_Frontend_Cli_Abstract
 */

/**
 * cli server for Admin
 *
 * This class handles cli requests for the Admin
 *
 * @package     Admin
 * @subpackage  Frontend
 */
class Admin_Frontend_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'importUser' => array(
            'description'   => 'Import new users into the Admin.',
            'params'        => array(
                'filenames'   => 'Filename(s) of import file(s) [required]',
                'definition'  => 'Name of the import definition [required]: for example admin_user_import_csv',
            )
        ),
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
     * import users
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function importUser($_opts)
    {
        $args = $_opts->getRemainingArgs();
            
        // get csv importer
        $definitionBackend = new Tinebase_ImportExportDefinition();
        $definitionName = array_pop($args);
        $definition = $definitionBackend->getByProperty($definitionName);
        $importer = new $definition->plugin($definition, Tinebase_User::factory(Tinebase_User::getConfiguredBackend()));
        
        // get mapping and container (from config file)
        /*
        if(file_exists($this->_configFilename)) {
            $config = new Zend_Config(require $this->_configFilename);
        } else {
            echo "Import config file not found.\n";
        }
        */
        
        // loop files in argv
        foreach ($args as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $result = $importer->import($filename);
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                continue;
            }
            
            echo "Imported " . $result['totalcount'] . " records.\n";
            
            // import (check if dry run)
            /*
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
            */       
        }
    }
}
