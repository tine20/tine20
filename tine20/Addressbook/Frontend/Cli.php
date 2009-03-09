 <?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * cli server for addressbook
 *
 * This class handles cli requests for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Frontend_Cli extends Tinebase_Application_Frontend_Cli_Abstract
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
     * 
     * @todo remove obsolete code
     */
    public function import($_opts)
    {
        parent::_import($_opts, Addressbook_Controller_Contact::getInstance());        
    }
    
    /**
     * quick hack to export csv's
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function export($_opts)
    {
        $containerId = 1;
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'singleContainer'),
            array('field' => 'container',     'operator' => 'equals',   'value' => $containerId     ),
        ));

        $csvExporter = new Addressbook_Export_Csv();
        
        $csvExporter->exportContacts($filter, TRUE);        
    }    
}
