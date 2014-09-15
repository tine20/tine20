<?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for addressbook
 *
 * This class handles cli requests for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
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
                'definition'  => 'Name of the import definition or filename [required] -> for example admin_user_import_csv(.xml)',
            )
        ),
        'export' => array(
            'description'   => 'Exports contacts as csv data to stdout',
            'params'        => array(
                'addressbookId' => 'only export contcts of the given addressbook',
                'tagId'         => 'only export contacts having the given tag'
            )
        )
    );
    
    /**
     * import contacts
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function import($_opts)
    {
        parent::_import($_opts);
    }
    
    /**
     * export contacts csv to STDOUT
     * 
     * NOTE: exports contacts in container id 1 by default. id needs to be changed in the code.
     *
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo allow to pass container id (and maybe more filter options) as param
     */
    public function export($_opts)
    {
        $containerId = 1;
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',     'operator' => 'equals',   'value' => $containerId     )
        ));

        $csvExporter = new Addressbook_Export_Csv($filter, null, array('toStdout' => true));
        
        $csvExporter->generate();
    }
    
    /**
     * create sample data
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function sampledata($_opts)
    {
        echo 'importing data ...';
        include '/var/www/tine20/Addressbook/sampledata.php';
        $controller = Addressbook_Controller_Contact::getInstance();
        $contact = array();
        
        for ($i=0; $i<10; $i++) {
            
            // create a company
            $contact['org_name'] = $sampledata['companyNames'][array_rand($sampledata['companyNames'])] . ' ' .
                                   $sampledata['companyDesc'][array_rand($sampledata['companyDesc'])] . ' ' .
                                   $sampledata['companyFrom'][array_rand($sampledata['companyFrom'])];
                                   
            $randCompNumber = array_rand($sampledata['companyPlz']);
            $contact['adr_one_street']     = $sampledata['companyStreet'][array_rand($sampledata['companyStreet'])];
            $contact['adr_one_postalcode'] = $sampledata['companyPlz'][$randCompNumber];
            $contact['adr_one_locality']   = $sampledata['companyOrt'][$randCompNumber];
            
            for ($j=0; $j<10; $j++) {
                // create person
                $contact['tel_work']           = $sampledata['companyDialcode'][$randCompNumber] . rand(2456, 871234);
                $contact['tel_fax']            = $contact['tel_work'] . '9';
                $contact['tel_cell']           = $sampledata['mobileDialcode'][array_rand($sampledata['mobileDialcode'])] . rand(245634, 87123224);
                $contact['role']               = $sampledata['position'][array_rand($sampledata['position'])];
                
                $randNameNumber = array_rand($sampledata['personFirstName']);
                $contact['n_given']            = $sampledata['personFirstName'][$randNameNumber];
                // todo: generate salutation even maile / odd femail
                $contact['n_family']            = $sampledata['personLastName'][array_rand($sampledata['personLastName'])];
                
                $randPersNumber = array_rand($sampledata['personPlz']);
                $contact['adr_two_street']     = $sampledata['personStreet'][array_rand($sampledata['personStreet'])];
                $contact['adr_two_postalcode'] = $sampledata['personPlz'][$randPersNumber];
                $contact['adr_two_locality']   = $sampledata['personOrt'][$randPersNumber];
                
                $contact['tel_home']           = $sampledata['personDialcode'][$randPersNumber] . rand(2456, 871234);
                $contact['tel_cell_private']   = $sampledata['mobileDialcode'][array_rand($sampledata['mobileDialcode'])] . rand(245634, 87123224);
                
                $contact['container_id'] = 133;
                $contactObj = new Addressbook_Model_Contact($contact, true);
                
                print_r($contactObj->toArray());
                
                $controller->create($contactObj);
            }
        }
    }

    /**
     * remove autogenerated contacts
     * 
     * @param Zend_Console_Getopt $opts
     * 
     * @todo use OR filter for different locales
     */
    public function removeAutogeneratedContacts($opts)
    {
        $this->_addOutputLogWriter(4);
        
        if (! Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            throw new Addressbook_Exception('Calendar application not installed');
        }
        
        $params = $this->_parseArgs($opts);
        
        $languages = isset($params['languages']) ? $params['languages'] : array('en', 'de');
        
        foreach ($languages as $language) {
            $locale = new Zend_Locale($language);
            
            $translation = Tinebase_Translation::getTranslation('Calendar', $locale);
            // search all contacts with note "This contact has been automatically added by the system as an event attender"
            $noteFilter = new Addressbook_Model_ContactFilter(array(
                array('field' => 'note', 'operator' => 'equals', 'value' => 
                    $translation->_('This contact has been automatically added by the system as an event attender')),
            ));
            $contactIdsToDelete = Addressbook_Controller_Contact::getInstance()->search($noteFilter, null, false, /* $_onlyIds = */ true);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " About to delete " . count($contactIdsToDelete) . ' contacts ...');
            
            $contactBackend = new Addressbook_Backend_Sql();
            $number = $contactBackend->delete($contactIdsToDelete);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " Deleted " . $number . ' autogenerated contacts for language ' . $language);
        }
    }
}
