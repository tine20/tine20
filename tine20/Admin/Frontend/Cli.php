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
 */

/**
 * cli server for Admin
 *
 * This class handles cli requests for the Admin
 *
 * @package     Admin
 * @subpackage  Frontend
 */
class Admin_Frontend_Cli extends Tinebase_Application_Frontend_Cli_Abstract
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
     * import users
     *
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo remove obsolete code
     */
    public function importUser($_opts)
    {
        parent::_import($_opts, Tinebase_User::factory(Tinebase_User::getConfiguredBackend()));
    }
}
