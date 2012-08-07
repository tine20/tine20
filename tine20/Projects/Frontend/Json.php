<?php
/**
 * Tine 2.0
 * @package     Projects
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * This class handles all Json requests for the Projects application
 *
 * @package     Projects
 * @subpackage  Frontend
 */
class Projects_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Projects_Controller_Project
     */
    protected $_controller = NULL;
    
    protected $_relatableModels = array('Projects_Model_Project');
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Projects';
        $this->_controller = Projects_Controller_Project::getInstance();
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchProjects($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Projects_Model_ProjectFilter', TRUE);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getProject($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveProject($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'Project');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteProjects($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }

    /**
     * Returns registry data
     *
     * @return array
     */
    public function getRegistryData()
    {
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName)->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();
        
        return array(
            'defaultContainer' => $defaultContainerArray
        );
    }
}
