<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * app name
     * 
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * search file/directory nodes
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     * 
     * @todo perhaps we can add searchCount() to the controller later and replace the count method TOTALCOUNT_COUNTRESULT
     */
    public function searchNodes($filter, $paging)
    {
        //$nodeFilter = $this->_convertContainerToParentIdFilter($filter);
        $result = $this->_search($filter, $paging, Filemanager_Controller_Node::getInstance(), 'Tinebase_Model_Tree_NodeFilter', FALSE, self::TOTALCOUNT_COUNTRESULT);
        
        return $result;
    }
    
    /**
     * convert generic container filter (with path) to node filter (with parent id)
     * 
     * @param array $_filter
     * @return array
     */
    protected function _convertContainerToParentIdFilter($_filter)
    {
        $basePath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));
        
        $result = array();
        foreach ($_filter as $filterData) {
            if ($filterData['field'] === 'container_id') {
                $parentIdFilter = array('field' => 'parent_id', 'operator' => 'in');
                $filterData['field'] = 'parent_id';
                $parentIds = array();
                foreach ((array) $filterData['value'] as $value) {
                    if (array_key_exists('path', $value)) {
                        $path = $basePath . $value['path'];
                        $node = Tinebase_FileSystem::getInstance()->stat($path);
                        $parentIds[] = $node->getId();
                    }
                }
                $parentIdFilter['value'] = $parentIds;
                $result[] = $parentIdFilter;
                
            } else {
                $result[] = $filterData;
            }
        }
        
        return $result;
    }
}
