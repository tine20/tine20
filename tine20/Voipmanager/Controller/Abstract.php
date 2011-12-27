<?php
/**
 * Abstract controller for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
abstract class Voipmanager_Controller_Abstract extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * the central caching object
     *
     * @var Zend_Cache_Core
     */
    protected $_cache = NULL;
    
    /**
     * prefix for cache id
     * 
     * @var string
     */
    protected $_cacheIdPrefix = NULL;
    
    /**
    * inspect update of one record (before update)
    *
    * @param   Tinebase_Record_Interface $_record      the update record
    * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
    * @return  void
    */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($this->_cacheIdPrefix !== NULL) {
            $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
    }
    
    /**
    * get by id
    * - results are cached
    *
    * @param string $_id the id of the peer
    * @return Voipmanager_Model_Snom_Location
    */
    public function get($_id)
    {
        $id = Tinebase_Record_Abstract::convertId($_id, $this->_modelName);
        if ($this->_cacheIdPrefix && $this->_cache) {
            $cacheId = $this->_cacheIdPrefix . $id;
            if ($this->_cache->test($id)) {
                $result = $this->_cache->load($id);
            } else {
                $result = $this->_backend->get($id);
                $this->_cache->save($result, $cacheId, array($this->_cacheIdPrefix), 5);
            }
        } else {
            $result = $this->_backend->get($id);
        }
    
        return $result;
    }
}
