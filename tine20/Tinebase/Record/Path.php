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

    /**
     * generates path for the record
     *
     * @param Tinebase_Record_Abstract $record
     * @return Tinebase_Record_RecordSet
     *
     * TODO what about acl? the account who creates the path probably does not see all relations ...
     */
    public function generatePathForRecord($record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Generate path for ' . get_class($record) . ' record with id ' . $record->getId());

        // fetch full record + check acl
        $recordController = Tinebase_Core::getApplicationInstance(get_class($record));
        $record = $recordController->get($record->getId());


        $currentPaths = Tinebase_Record_Path::getInstance()->getPathsForRecords($record);

        $newPaths = new Tinebase_Record_RecordSet('Tinebase_Model_Path');

        // fetch all parent -> child relations and add to path
        $newPaths->merge($this->_getPathsOfRecord($record));

        if (method_exists($recordController, 'generatePathForRecord')) {
            $newPaths->merge($recordController->generatePathForRecord($record));
        }

        //compare currentPaths with newPaths to find out if we need to make subtree updates
        //we should do this before the new paths of the current record have been persisted to DB!
        $currentShadowPathOffset = array();
        foreach($currentPaths as $offset => $path) {
            $currentShadowPathOffset[$path->shadow_path] = $offset;
        }

        $newShadowPathOffset = array();
        foreach($newPaths as $offset => $path) {
            $newShadowPathOffset[$path->shadow_path] = $offset;
        }

        $toDelete = array();
        $anyOldOffset = null;
        foreach($currentShadowPathOffset as $shadowPath => $offset) {
            $anyOldOffset = $offset;

            // parent path has been deleted!
            if (false === isset($newShadowPathOffset[$shadowPath])) {
                $toDelete[] = $shadowPath;
                continue;
            }

            $currentPath = $currentPaths[$offset];
            $newPath = $newPaths[$newShadowPathOffset[$shadowPath]];

            // path changed (a title was updated or similar)
            if ($currentPath->path !== $newPath->path) {
                // update ... set path = REPLACE(path, $currentPath->path, $newPath->path) where shadow_path LIKE '$shadowPath/%'
                $this->_backend->replacePathForShadowPathTree($shadowPath, $currentPath->path, $newPath->path);
            }

            unset($newShadowPathOffset[$shadowPath]);
        }

        // new parents
        if (count($newShadowPathOffset) > 0 && null !== $anyOldOffset) {
            $anyPath = $currentPaths[$anyOldOffset];
            $newParents = array_values($newShadowPathOffset);
            foreach ($newParents as $newParentOffset) {
                $newParent = $newPaths[$newParentOffset];

                // insert into ... select
                // REPLACE(path, $anyPath->path, $newParent->path) as path,
                // REPLACE(shadow_path, $anyPath->shadow_path, $newParent->shadow_path) as shadow_path
                // from ... where shadow_path LIKE '$anyPath->shadow_path/%'
                $this->_backend->copyTreeByShadowPath($anyPath->shadow_path, $newParent->path, $anyPath->path, $newParent->shadow_path, $anyPath->shadow_path);
            }
        }

        // execute deletes only now, important to make 100% sure "new parents" just above still has data to work on!
        foreach($toDelete as $delete) {
            // delete where shadow_path LIKE '$delete/%'
            $this->_backend->deleteForShadowPathTree($delete);
        }


        // delete current paths of this record
        $this->deletePathsForRecord($record);

        // recreate new paths of this record
        foreach ($newPaths as $path) {
            $this->_backend->create($path);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Created ' . count($newPaths) . ' paths.');
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($newPaths->toArray(), true));


        return $newPaths;
    }

    /**
     * delete all record paths
     *
     * @param $record
     * @return int
     *
     * TODO add acl check?
     */
    public function deletePathsForRecord($record)
    {
        return $this->_backend->deleteByProperty($record->getId(), 'record_id');
    }

    /**
     * getPathsForRecords
     *
     * @param Tinebase_Record_Interface|Tinebase_Record_RecordSet $records
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotFound
     */
    public function getPathsForRecords($records)
    {
        $ids = $records instanceof Tinebase_Record_Interface ? array($records->getId()) : $records->getArrayOfIds();

        return $this->search(new Tinebase_Model_PathFilter(array(
            array('field' => 'record_id', 'operator' => 'in', 'value' => $ids)
        )));
    }
}
