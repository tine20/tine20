<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add verbose + dryrun to updateTimeaccountGrants()
 */

/**
 * cli server for timetracker
 *
 * This class handles cli requests for the timetracker
 *
 * @package     Addressbook
 */
class Timetracker_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * help array with function names and param descriptions
     * 
     * @return void
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
     * add manage billable to all users of all timeaccounts
     * 
     * @return void
     */
    public function allBillable()
    {
        $containerController = Tinebase_Container::getInstance();
        
        $allTAs = Timetracker_Controller_Timeaccount::getInstance()->search(new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '')
        )));
        foreach ($allTAs->container_id as $container_id) {
            $allGrants = $containerController->getGrantsOfContainer($container_id, true);
            
            foreach ($allGrants as $grants) {
                // set manage billable grant;
                $grants->{Tinebase_Model_Grants::GRANT_DELETE} = TRUE;
            }
            $containerController->setGrants($container_id, $allGrants, true);
            echo '.';
            
        }
        echo "done.\n";
    }

    /**
     * replace single user accounts with one or more groups for each timeaccount in filter
     * 
     * @return void
     */
    public function updateTimeaccountGrants()
    {
        // get tas matching filter
        $filter = new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => 'some value')
        ));
        $tas = Timetracker_Controller_Timeaccount::getInstance()->search($filter);
        
        // group ids to set book own grant for
        $groupIds = array(1, 2);
        
        echo 'Updating timeaccount grants';
        // loop tas and update with new grants
        foreach ($tas as $ta) {
            $grants = array();
            foreach($groupIds as $id) {
                $grants[] = array(
                    'account_id'    => $id,
                    'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                    'book_own'      => TRUE,
                );
            }
            $ta->grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', $grants);
            
            // @todo add dryrun
            Timetracker_Controller_Timeaccount::getInstance()->update($ta);
            // @todo verbose
            //echo 'updating ' . $ta->number . "\n";
            echo '.';
        }
        echo "done.\n";
    }
    

    /**
     * search and show duplicate timeaccounts
     * 
     * @return void
     */
    public function searchDuplicateTimeaccounts()
    {
        $filter = new Timetracker_Model_TimeaccountFilter(array(array(
            'field' => 'showClosed', 
            'operator' => 'equals', 
            'value' => FALSE
        )));
        
        $duplicates = parent::_searchDuplicates(Timetracker_Controller_Timeaccount::getInstance(), $filter, 'title');
        
        echo 'Found ' . (count($duplicates) / 2) . ' Timeaccount duplicate(s):' . "\n";
        
        print_r($duplicates);
    }
}
