<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Path
 * 
 * filters own ids match result of path search
 * 
 * <code>
 *      'contact'        => array('filter' => 'Tinebase_Model_Filter_Path', 'options' => array(
 *      )
 * </code>     
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Path extends Tinebase_Model_Filter_Text
{
    protected $_controller = null;

    /**
     * @var array
     */
    protected $_pathRecordIds = null;

    /**
     * get path controller
     * 
     * @return Tinebase_Record_Path
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Tinebase_Record_Path::getInstance();
        }
        
        return $this->_controller;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH) ||
                empty($this->_value)) {
            return;
        }

        $modelName = $_backend->getModelName();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
            . 'Adding Path filter for: ' . $modelName);
        
        $this->_resolvePathIds($modelName);

        $idField = (isset($this->_options['idProperty']) || array_key_exists('idProperty', $this->_options)) ? $this->_options['idProperty'] : 'id';
        $db = $_backend->getAdapter();
        $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);
        if (empty($this->_pathRecordIds)) {
            $_select->where('1=0');
        } else {
            $_select->where($db->quoteInto("$qField IN (?)", $this->_pathRecordIds));
        }
    }
    
    /**
     * resolve foreign ids
     */
    protected function _resolvePathIds($_model)
    {
        if (! is_array($this->_pathRecordIds)) {
             $paths = $this->_getController()->search(new Tinebase_Model_PathFilter(array(
                array('field' => 'query', 'operator' => $this->_operator, 'value' => $this->_value)
            )));

            $this->_pathRecordIds = array();
            if ($paths->count() > 0) {
                if (!is_array($this->_value)) {
                    $this->_value = array($this->_value);
                }
                $searchTerms = array();
                foreach ($this->_value as $value) {
                    //replace full text meta characters
                    //$value = str_replace(array('+', '-', '<', '>', '~', '*', '(', ')', '"'), ' ', $value);
                    $value = preg_replace('#\W#u', ' ', $value);
                    // replace multiple spaces with just one
                    $searchTerms = array_merge($searchTerms, explode(' ', preg_replace('# +#u', ' ', trim($value))));
                }

                if (count($searchTerms) < 1) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                        ' found paths, but search terms array is empty. value: ' . print_r($this->_value, true));
                    return;
                }

                array_walk($searchTerms, function(&$val) {$val = mb_strtolower($val);});
                $hitNeighbours = array();
                $hitIds = array();

                /** @var Tinebase_Model_Path $path */
                foreach($paths as $path) {
                    $pathParts = explode('/', trim($path->path, '/'));
                    $shadowPathParts = explode('/', trim($path->shadow_path, '/'));
                    $offset = 0;
                    $hit = false;
                    foreach($pathParts as $pathPart) {
                        $pathPart = mb_strtolower($pathPart);

                        $shadowPathPart = $shadowPathParts[$offset++];
                        $model = substr($shadowPathPart, 1, strpos($shadowPathPart, '}') - 1);
                        $id = substr($shadowPathPart, strpos($shadowPathPart, '}') + 1);
                        if (false !== ($pos = strpos($id, '{'))) {
                            $id = substr($id, 0, $pos - 1);
                        }

                        $newHit = true;
                        foreach($searchTerms as $searchTerm) {
                            if (false === strpos($pathPart, $searchTerm)) {
                                $newHit = false;
                                break;
                            }
                        }
                        if (true === $newHit) {
                            $hitIds[] = $id;
                            $hit = true;
                            continue;
                        }
                        if (false === $hit) {
                            continue;
                        }

                        if ($model !== $_model) {
                            continue;
                        }

                        $hitNeighbours[] = $id;
                        $hit = false;
                    }
                }

                if (count($hitNeighbours) > 0) {
                    $this->_pathRecordIds = $hitNeighbours;
                } else {
                    $this->_pathRecordIds = $hitIds;
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign ids: ' 
            . print_r($this->_pathRecordIds, TRUE));
    }
}
