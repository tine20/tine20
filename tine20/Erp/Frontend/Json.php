<?php
/**
 * Tine 2.0
 * @package     Erp
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add functions again (__call interceptor doesn't work because of the reflection api)
 * @todo        check if we can add these functions to the reflection without implementing them here
 */

/**
 *
 * This class handles all Json requests for the Erp application
 *
 * @package     Erp
 * @subpackage  Frontend
 */
class Erp_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Erp';
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        $controller = Erp_Controller_Contract::getInstance();
        return $this->_search($filter, $paging, $controller, 'Erp_Model_ContractFilter');
    }     
}
