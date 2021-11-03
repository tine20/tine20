<?php

/**
 * Tine 2.0
 * @package     Projects
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_configuredModels = [
        Projects_Model_Project::MODEL_NAME_PART,
    ];

    protected $_relatableModels = array('Projects_Model_Project');

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Projects';
    }

    /**
     * Returns registry data
     *
     * @return array
     */
    public function getRegistryData()
    {
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer(Projects_Model_Project::class)->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();
        
        return array(
            'defaultContainer' => $defaultContainerArray
        );
    }
}
