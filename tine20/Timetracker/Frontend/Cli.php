 <?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * cli server for timetracker
 *
 * This class handles cli requests for the timetracker
 *
 * @package     Addressbook
 */
class Timetracker_Frontend_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'allBillable' => array(
            'description'   => 'give manage_billable to all users of all Timeaccounts',
            'params'        => array(
                //'filenames'   => 'Filename(s) of import file(s) [required]',
                //'format'     => 'Import file format (default: csv) [optional]',
                //'config'     => 'Mapping config file (default: importconfig.inc.php) [optional]',
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
    
    public function allBillable()
    {
        $containerController = Tinebase_Container::getInstance();
        
        $allTAs = Timetracker_Controller_Timeaccount::getInstance()->search(new Timetracker_Model_TimeaccountFilter());
        foreach ($allTAs->container_id as $container_id) {
            $allGrants = $containerController->getGrantsOfContainer($container_id, true);
            
            foreach ($allGrants as $grants) {
                // set manage billable grant;
                $grants->deleteGrant = TRUE;
            }
            $containerController->setGrants($container_id, $allGrants, true);
            
        }
    }
 
}
