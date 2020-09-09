<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller for record paths
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Path extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Path';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Alarm
     */
    private static $instance = NULL;

    protected $_rebuildQueue = array();

    protected $_recursionCounter = 0;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Path_Backend_Sql();
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Record_Path
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addToRebuildQueue(Tinebase_Record_Interface $_rebuildPathParams)
    {
        // attention, this code contains recursion prevention logic, do not change this, except you understand that logic!
        // see __CLASS__::_workRebuildQueue function
        $shadowPathPart = $_rebuildPathParams->getShadowPathPart();
        if (!isset($this->_rebuildQueue[$shadowPathPart])) {
            $this->_rebuildQueue[$shadowPathPart] = [$_rebuildPathParams];
        }
    }

    /**
     * getPathsForRecords
     *
     * no acl check done in here
     *
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Record_RecordSet
     */
    public function getPathsForRecord(Tinebase_Record_Interface $_record)
    {
        $filter = new Tinebase_Model_PathFilter(array(
            array('field' => 'shadow_path', 'operator' => 'contains', 'value' => $_record->getShadowPathPart())
        ));

        return $this->_backend->search($filter);
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_RecordSet $_paths
     */
    public function cutTailAfterRecord(Tinebase_Record_Interface $_record, Tinebase_Record_RecordSet $_paths)
    {
        $shadowPart = ltrim($_record->getShadowPathPart(), '/');
        $shadowPaths = array();
        $remove = array();

        /** @var Tinebase_Model_Path $path */
        foreach ($_paths as $path) {
            $pathParts = explode('/', $path->path);
            $shadowPathParts = explode('/', $path->shadow_path);
            $countPathParts = count($shadowPathParts);
            $offset = 0;
            foreach ($shadowPathParts as $sPart) {
                ++$offset;
                if (0 === strpos($sPart, $shadowPart)) {
                    break;
                }
            }
            if ($countPathParts <= $offset) {
                continue;
            }
            if (1 === $offset) {
                $remove[] = $path->getId();
                continue;
            }
            $shadow_path = preg_replace('/\{[^\}]+\}$/', '', join('/', array_slice($shadowPathParts, 0, $offset)));
            if (!isset($shadowPaths[$shadow_path])) {
                $path->shadow_path = $shadow_path;
                $path->path = join('/', array_slice($pathParts, 0, $offset));
                $shadowPaths[$path->shadow_path] = true;
            } else {
                $remove[] = $path->getId();
            }
        }

        foreach ($remove as $id) {
            $_paths->removeById($id);
        }
    }

    /**
     * getPathsForShadowPathPart
     *
     * no acl check done in here
     *
     * @param string $_shadowPathPart
     * @return Tinebase_Record_RecordSet
     */
    public function getPathsForShadowPathPart($_shadowPathPart)
    {
        $filter = new Tinebase_Model_PathFilter(array(
            array('field' => 'shadow_path', 'operator' => 'contains', 'value' => $_shadowPathPart)
        ));

        return $this->_backend->search($filter);
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param string $oldPathPart
     */
    public function pathReplace(Tinebase_Record_Interface $record, $oldPathPart)
    {
        $paths = $this->getPathsForRecord($record);
        $newPathPart = $record->getPathPart();

        /** @var Tinebase_Model_Path $path */
        foreach($paths as $path) {
            if (false === ($pos = mb_strpos($path->path, $oldPathPart))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                    . __LINE__ . 'could not find old part part: ' . $oldPathPart . ' in path: ' . $path->path);
                $this->_backend->delete($path->getId());
                continue;
            }
            if (false !== mb_strpos($path->path, $oldPathPart, $pos + 1)) {
                // TODO split by /, find right part, replace it, glue it with /
                // TODO write test for this code path!!!!
                $this->_backend->delete($path->getId());
                continue;
            } else {
                $path->path = str_replace($oldPathPart, $newPathPart, $path->path);
            }

            $this->_backend->update($path);
        }
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface|null $_oldRecord the record before the update including relatedData / relations (but only those visible to the current user)
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function rebuildPaths(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord = null)
    {
        $this->_recursionCounter++;

        if (null !== $_oldRecord && $_record->getId() !== $_oldRecord->getId()) {
            throw new Tinebase_Exception_UnexpectedValue('id of current and updated record must not change');
        }

        if (null !== $_oldRecord && ($oldPathPart = $_oldRecord->getPathPart()) !== $_record->getPathPart()) {
            $this->pathReplace($_record, $oldPathPart);
        }

        $ownShadowPathPart = $_record->getShadowPathPart();
        $this->_rebuildQueue[$ownShadowPathPart] = false;

        try {
            $pathNeighbours = $_record->getPathNeighbours();
        } catch (Tinebase_Exception_Record_StopPathBuild $e) {
            $this->_recursionCounter--;
            if (0 === $this->_recursionCounter)
            {
                $this->_rebuildQueue = [];
            }
            return;
        }


        $paths = $this->getPathsForRecord($_record);
        $pathsPathNeighbours = $paths->__call('getNeighbours', array($ownShadowPathPart));
        $oldPathParents = array();
        $oldPathChildren = array();
        foreach($pathsPathNeighbours as $neighbours) {
            if(isset($neighbours['parent'])) {
                $oldPathParents[$neighbours['parent']] = true;
            }
            if(isset($neighbours['child'])) {
                $oldPathChildren[$neighbours['child']] = true;
            }
        }

        $newParents = array();
        /** @var Tinebase_Record_Interface $parent */
        foreach ($pathNeighbours['parents'] as $parent) {
            $pathPart = trim($parent->getShadowPathPart(null, $_record), '/');
            if (isset($oldPathParents[$pathPart])) {
                unset($oldPathParents[$pathPart]);
            } else {
                if (isset($newParents[$pathPart])) {
                    throw new Tinebase_Exception_UnexpectedValue('generated path part twice! ' . $pathPart);
                }
                $newParents[$pathPart] = $parent;
            }
        }

        $newChildren = array();
        /** @var Tinebase_Record_Interface $child */
        foreach ($pathNeighbours['children'] as $child) {
            $pathPart = $child->getShadowPathPart($_record);
            if (isset($oldPathChildren[$pathPart])) {
                unset($oldPathChildren[$pathPart]);
            } else {
                if (isset($newChildren[$pathPart])) {
                    throw new Tinebase_Exception_UnexpectedValue('generated path part twice! ' . $pathPart);
                }
                $newChildren[$pathPart] = $child;
            }
        }


        if (count($oldPathChildren) > 0 || count($oldPathParents) > 0) {
            $toDelete = array();
            foreach($oldPathParents as $pathPart => $tmp) {
                $toDelete[] = $pathPart . $ownShadowPathPart;
            }
            foreach($oldPathChildren as $pathPart => $tmp) {
                $toDelete[] = trim($ownShadowPathPart, '/') . $pathPart;
            }

            $this->deleteShadowPathParts($toDelete);

            // eventually we alreasy added some of our new parents or children as the above delete triggers rebuilds...
            // we start start over

            $this->rebuildPaths($_record, $_oldRecord);
        } else {

            foreach ($newChildren as $child) {
                $this->addPathChild($_record, $child);
            }
            foreach ($newParents as $parent) {
                $this->addPathParent($_record, $parent);
            }
        }

        $this->_workRebuildQueue();

        $this->_recursionCounter--;
        if (0 === $this->_recursionCounter)
        {
            $this->_rebuildQueue = [];
        }
    }

    protected function _workRebuildQueue()
    {
        if (!empty($this->_rebuildQueue)) {
            //attention this is recursion prevention logic, don't change this light headed
            $queue = array_values($this->_rebuildQueue);
            $this->_rebuildQueue = array_fill_keys(array_keys($this->_rebuildQueue), false);

            foreach($queue as $params) {
                if (false !== $params) {
                    call_user_func_array(array($this, 'rebuildPaths'), $params);
                }
            }
        }
    }

    /**
     * @param Tinebase_Record_RecordSet $_paths
     * @param Tinebase_Record_Interface $_record
     * @param string|null $_recordShadowPathPart
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _getUniqueTreeTail(Tinebase_Record_RecordSet $_paths, Tinebase_Record_Interface $_record, $_recordShadowPathPart = null)
    {
        if (null === $_recordShadowPathPart) {
            $_recordShadowPathPart = $_record->getShadowPathPart();
        }

        $uniquePaths = array();
        /** @var Tinebase_Model_Path $path */
        foreach ($_paths as $path) {
            if (false === ($pos = strpos($path->shadow_path, $_recordShadowPathPart))) {
                throw new Tinebase_Exception_UnexpectedValue('shadow path: ' . $path->shadow_path . ' doesn\'t contain: ' . $_recordShadowPathPart);
            }
            $tailPart = substr($path->shadow_path, $pos);

            if (!isset($uniquePaths[$tailPart])) {
                $pathDept = count(explode('/', trim($tailPart, '/')));
                $pathParts = array_reverse(explode('/', trim($path->path, '/')));
                $newPath = '';
                $i = 0;
                while($i < $pathDept) {
                    $newPath = '/' . $pathParts[$i++] . $newPath;
                }

                $uniquePaths[$tailPart] = array('path' => $newPath, 'record' => $path);
            }
        }
        if (count($uniquePaths) === 0) {
            $pathPart = $_record->getPathPart();
            $uniquePaths[$_recordShadowPathPart] = array('path' => $pathPart);
        }

        return $uniquePaths;
    }

    /**
     * @param Tinebase_Record_RecordSet $_paths
     * @param Tinebase_Record_Interface $_record
     * @param string|null $_recordShadowPathPart
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _getUniqueTreeHead(Tinebase_Record_RecordSet $_paths, Tinebase_Record_Interface $_record, $_recordShadowPathPart = null)
    {
        if (null === $_recordShadowPathPart) {
            $_recordShadowPathPart = $_record->getShadowPathPart();
        }
        $lengthShadowPathPart = strlen($_recordShadowPathPart);

        $uniquePaths = array();
        /** @var Tinebase_Model_Path $path */
        foreach ($_paths as $path) {
            if (false === ($pos = strpos($path->shadow_path, $_recordShadowPathPart))) {
                throw new Tinebase_Exception_UnexpectedValue('shadow path: ' . $path->shadow_path . ' doesn\'t contain: ' . $_recordShadowPathPart);
            }
            $headPart = substr($path->shadow_path, 0, $pos + $lengthShadowPathPart);

            if (!isset($uniquePaths[$headPart])) {
                $pathDept = count(explode('/', trim($headPart, '/')));
                $pathParts = explode('/', trim($path->path, '/'));
                $newPath = '';
                $i = 0;
                while($i < $pathDept) {
                    $newPath .= '/' . $pathParts[$i++];
                }

                // remove last {TYPE} if present
                if (false !== ($pos = strrpos($newPath, '}')) && $pos === strlen($newPath) - 1) {
                    $newPath = substr($newPath, 0, strrpos($newPath, '{'));
                }

                $uniquePaths[$headPart] = array('path' => $newPath, 'record' => $path);
            }
        }
        if (count($uniquePaths) === 0) {
            $pathPart = $_record->getPathPart();
            $uniquePaths[$_recordShadowPathPart] = array('path' => $pathPart);
        }

        return $uniquePaths;
    }

    /**
     * @param array $uniquePaths
     * @param array $uniqueChildPaths
     * @param string $pathType
     * @param string $_recordShadowPathPart
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _joinPathTrees(array $_uniquePaths, array $_uniqueChildPaths, $_pathType, $_recordShadowPathPart)
    {
        foreach($_uniquePaths as $shadowPathPart => $data) {
            $reUsePath = (isset($data['record']) && $data['record']->shadow_path === $shadowPathPart);

            foreach($_uniqueChildPaths as $childShadowPathPart => $childData) {
                if (isset($childData['record']) && $childData['record']->shadow_path === $childShadowPathPart) {
                    $path = $childData['record'];
                } elseif (true === $reUsePath) {
                    $path = $data['record'];
                    $reUsePath = false;
                } else {
                    $path = new Tinebase_Model_Path(array(), true);
                }
                $path->path = $data['path'] . $_pathType . $childData['path'];
                $path->shadow_path = $shadowPathPart . $_pathType . $childShadowPathPart;

                if (($count = substr_count($path->shadow_path, $_recordShadowPathPart)) !== 1) {
                    throw new Tinebase_Exception_UnexpectedValue('newly created shadow path: ' . $path->shadow_path . ' contains ' . $count . ' times the records shadow path: ' . $_recordShadowPathPart);
                }

                if (!empty($path->getId())) {
                    $this->_backend->update($path);
                } else {
                    $this->_backend->create($path);
                }
            }

            if (true === $reUsePath) {
                $this->_backend->delete($data['record']->getId());
            }
        }
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_child
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function addPathChild($_record, $_child)
    {
        $recordShadowPathPart = $_record->getShadowPathPart();
        $childShadowPathPart = $_child->getShadowPathPart();

        $paths = $this->getPathsForShadowPathPart($recordShadowPathPart);
        $childPaths = $this->getPathsForShadowPathPart($childShadowPathPart);


        if ($paths->count() === 0 && $childPaths->count() === 0) {
            $path = new Tinebase_Model_Path(array(
                'path'          => $_record->getPathPart() . $_child->getPathPart($_record),
                'shadow_path'   => $recordShadowPathPart . $_child->getShadowPathPart($_record)
            ));
            $this->_backend->create($path);
            return;
        }


        $uniqueChildPaths = $this->_getUniqueTreeTail($childPaths, $_child, $childShadowPathPart);
        $uniquePaths = $this->_getUniqueTreeHead($paths, $_record, $recordShadowPathPart);

        $pathType = $_child->getTypeForPathPart();

        $this->_joinPathTrees($uniquePaths, $uniqueChildPaths, $pathType, $recordShadowPathPart);
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_parent
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function addPathParent($_record, $_parent)
    {
        $recordShadowPathPart = $_record->getShadowPathPart();
        $parentShadowPathPart = $_parent->getShadowPathPart();

        $paths = $this->getPathsForShadowPathPart($recordShadowPathPart);
        $parentPaths = $this->getPathsForShadowPathPart($parentShadowPathPart);


        if ($paths->count() === 0 && $parentPaths->count() === 0) {
            $path = new Tinebase_Model_Path(array(
                'path'          => $_parent->getPathPart() . $_record->getPathPart($_parent),
                'shadow_path'   => $parentShadowPathPart . $_record->getShadowPathPart($_parent)
            ));
            $this->_backend->create($path);
            return;
        }

        $uniqueChildPaths = $this->_getUniqueTreeTail($paths, $_record, $recordShadowPathPart);
        $uniquePaths = $this->_getUniqueTreeHead($parentPaths, $_parent, $parentShadowPathPart);

        $pathType = $_parent->getTypeForPathPart();

        $this->_joinPathTrees($uniquePaths, $uniqueChildPaths, $pathType, $recordShadowPathPart);
    }

    /**
     * @param array $_shadowPathPart
     */
    public function deleteShadowPathParts(array $_shadowPathParts)
    {
        $ids = array();
        $paths = array();
        foreach ($_shadowPathParts as $shadowPathPart) {
            $filter = new Tinebase_Model_PathFilter(array(
                array('field' => 'shadow_path', 'operator' => 'contains', 'value' => $shadowPathPart)
            ));
            $result = $this->_backend->search($filter);
            foreach($result as $path) {
                if (!isset($ids[$path->getId()])) {
                    $ids[$path->getId()] = true;
                    $paths[] = $path;
                }
            }
        }

        $this->_backend->delete(array_keys($ids));

        $this->_workRebuildQueue();

        $recordIds = array();
        /** @var Tinebase_Model_Path $path */
        foreach($paths as $path) {
            foreach($path->getRecordIds() as $key => $data) {
                if (!isset($recordIds[$key])) {
                    $recordIds[$key] = $data;
                }
            }
        }

        $controllerCache = array();
        foreach($recordIds as $data) {
            $model = $data['model'];
            if (isset($controllerCache[$model])) {
                $controller = $controllerCache[$model]['controller'];
            } else {
                $controllerCache[$model] = [];
                $controller = $controllerCache[$model]['controller'] =
                    Tinebase_Core::getApplicationInstance($model, '', true);
                $controllerCache[$model]['doRightChecks'] = $controller->doRightChecks(false);
                $controllerCache[$model]['doContainerACLChecks'] = $controller->doContainerACLChecks(false);
                $controllerCache[$model]['sendNotifications'] = $controller->sendNotifications(false);
            }

            try {
                $record = $controller->get($data['id']);
            } catch(Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' could not get record during path rebuild, possibly concurrent deletion');
                continue;
            }

            $this->addToRebuildQueue($record);
        }

        foreach ($controllerCache as $data) {
            /** @var Tinebase_Controller_Record_Abstract $controller */
            $controller = $data['controller'];
            $controller->doRightChecks($data['doRightChecks']);
            $controller->doContainerACLChecks($data['doContainerACLChecks']);
            $controller->sendNotifications($data['sendNotifications']);
        }

        $this->_workRebuildQueue();
    }
}
