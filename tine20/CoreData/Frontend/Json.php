<?php
/**
 * Tine 2.0
 * @package     CoreData
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the CoreData application
 *
 * @package     CoreData
 * @subpackage  Frontend
 */
class CoreData_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = array();

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'CoreData';
    }

    public function getCoreData()
    {
        try {
            $result =  CoreData_Controller::getInstance()->getCoreData()->toArray();
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $result = array();
        }

        return array(
            'results'    => $result,
            'totalcount' => count($result)
        );
    }

    /**
     * Returns registry data
     * 
     * @return array
     */
    public function getRegistryData()
    {
        return array(
            'coreData' => $this->getCoreData()
        );
    }
}
