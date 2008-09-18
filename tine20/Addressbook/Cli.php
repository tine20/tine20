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
                'filenames'   => 'Filename(s) of import file(s) [required]',
                //'format'     => 'Import file format (default: csv) [optional]',
                //'config'     => 'Mapping config file (default: mapping.inc.php) [optional]',
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
     * @todo get mapping from config file
     */
    public function import($_opts)
    {
        $args = $_opts->getRemainingArgs();
            
        // get csv importer
        $importer = Addressbook_Import_Factory::factory('Csv');
        
        // set mapping
        $mapping = array(
            'adr_one_locality'      => 'Ort',
            'adr_one_postalcode'    => 'Plz',
            'adr_one_street'        => 'Straße',
            'org_name'              => 'Name1',
            'org_unit'              => 'Name2',
            'note'                  => array(
                'Mitarbeiter'       => 'inLab Spezi',
                'Anzahl Mitarbeiter' => 'ANZMitarbeiter',
                'Bemerkung'         => 'Bemerkung',
            ),
            'tel_work'              => 'TelefonZentrale',
            'tel_cell'              => 'TelefonDurchwahl',
            'n_family'              => 'Nachname',
            'n_given'               => 'Vorname',
            'n_prefix'              => array('Anrede', 'Titel'),
        );
        
        foreach ($args as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $records = $importer->read($filename, $mapping);
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                return FALSE;
            }
            
            // import (check if dry run)
            if (!$_opts->d) {
                if ($_opts->v) {
                    echo "importing ". count($records) ." records...";
                }
                $importedRecords = $importer->import($records);
                if ($_opts->v) {
                    echo "done.\n";
                }
                if ($_opts->v) {
                    foreach ($importedRecords as $contact) {
                        echo "Imported contact: " . $contact->n_full;
                    }   
                }
            } else {
                print_r($records->toArray());
            }        
        }
    }
}
