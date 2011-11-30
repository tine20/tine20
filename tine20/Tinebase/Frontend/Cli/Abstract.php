<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * abstract cli server
 *
 * This class handles cli requests
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array();
    
    /**
     * echos usage information
     *
     */
    public function getHelp()
    {
        foreach ($this->_help as $functionHelp) {
            echo $functionHelp['description']."\n";
            echo "parameters:\n";
            foreach ($functionHelp['params'] as $param => $description) {
                echo "$param \t $description \n";
            }
        }
    }
    
    /**
     * update or create import/export definition
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function updateImportExportDefinition(Zend_Console_Getopt $_opts)
    {
        $defs = $_opts->getRemainingArgs();
        if (empty($defs)) {
            echo "No definition given.\n";
            return FALSE;
        }
        
        if (! $this->_checkAdminRight()) {
            return FALSE; 
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        foreach ($defs as $definitionFilename) {
            Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFilename, $application);
            echo "Imported " . $definitionFilename . " successfully.\n";
        }
        
        return TRUE;
    }

    /**
     * set container grants
     * 
     * example usages: 
     * (1) $ php tine20.php --method=Calendar.setContainerGrants containerId=3339 accountId=15 accountType=group grants=readGrant
     * (2) $ php tine20.php --method=Timetracker.setContainerGrants namefilter="timeaccount name" accountId=15,30 accountType=group grants=book_own,manage_billable overwrite=1
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function setContainerGrants(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE; 
        }
        
        $data = $this->_parseArgs($_opts, array('accountId', 'grants'));
        
        $containers = $this->_getContainers($data);
        if (count($containers) == 0) {
            echo "No matching containers found.\n";
        } else {
            Admin_Controller_Container::getInstance()->setGrantsForContainers(
                $containers, 
                $data['grants'],
                $data['accountId'], 
                (array_key_exists('accountType', $data)) ? $data['accountType'] : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                (array_key_exists('overwrite', $data) && $data['overwrite'] == '1')
            );
            
            echo "Updated " . count($containers) . " container(s).\n";
        }
        
        return TRUE;
    }
    
    /**
     * get container for setContainerGrants
     * 
     * @param array $_params
     * @return Tinebase_Record_RecordSet
     * @throws Timetracker_Exception_UnexpectedValue
     */
    protected function _getContainers($_params)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $containerFilterData = array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $application->getId()),
        );
        
        if (array_key_exists('containerId', $_params)) {
            $containerFilterData[] = array('field' => 'id', 'operator' => 'equals', 'value' => $_params['containerId']);
        } else if (array_key_exists('namefilter', $_params)) {
            $containerFilterData[] = array('field' => 'name', 'operator' => 'contains', 'value' => $_params['namefilter']);
        } else {
            throw new Timetracker_Exception_UnexpectedValue('Parameter containerId or namefilter missing!');
        }
        
        $containers = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter($containerFilterData));
        
        return $containers;
    }
    
    /**
     * parses arguments (key1=value1 key2=value2 key3=subvalue1,subvalue2 ...)
     * 
     * @param Zend_Console_Getopt $_opts
     * @param array $_requiredKeys
     * @param string $_otherKey use this key for arguments without '='
     * @throws Tinebase_Exception_InvalidArgument
     * @return array
     */
    protected function _parseArgs(Zend_Console_Getopt $_opts, $_requiredKeys = array(), $_otherKey = 'other')
    {
        $args = $_opts->getRemainingArgs();
        
        $result = array();
        foreach ($args as $idx => $arg) {
            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg);
                if (strpos($value, ',') !== false) {
                    $value = explode(',', $value);
                }
                $value = str_replace('"', '', $value);
                $result[$key] = $value;
            } else {
                $result[$_otherKey][] = $arg;
            }
        }
        
        if (! empty($_requiredKeys)) {
            foreach ($_requiredKeys as $requiredKey) {
                if (! array_key_exists($requiredKey, $result)) {
                    throw new Tinebase_Exception_InvalidArgument('Required parameter not found: ' . $requiredKey);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * check admin right of application
     * 
     * @return boolean
     */
    protected function _checkAdminRight()
    {
        // check if admin for tinebase
        if (! Tinebase_Core::getUser()->hasRight($this->_applicationName, Tinebase_Acl_Rights::ADMIN)) {
            echo "No permission.\n";
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * import records
     *
     * @param Zend_Console_Getopt   $_opts
     */
    protected function _import($_opts)
    {
        $args = $this->_parseArgs($_opts, array(), 'filename');
        
        if ($_opts->d) {
            $args['dryrun'] = 1;
        }
        
        if (array_key_exists('definition', $args))  {       
            if (preg_match("/\.xml/", $args['definition'])) {
                $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                    $args['definition'],
                    Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
                ); 
            } else {
                $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($args['definition']);
            }
        
            $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, $args);
        } else if (array_key_exists('plugin', $args)) {
            $importer =  new $args['plugin']($args);
        } else {
            echo "You need to define a plugin OR a definition at least! \n";
            exit;
        }
        
        // loop files in argv
        foreach ((array) $args['filename'] as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $result = $importer->importFile($filename);
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                continue;
            }
            
            echo "Imported " . $result['totalcount'] . " records. Import failed for " . $result['failcount'] . " records. \n";
            if (isset($result['duplicatecount']) && ! empty($result['duplicatecount'])) {
                echo "Found " . $result['duplicatecount'] . " duplicates.\n";
            }
                        
            // import (check if dry run)
            if ($_opts->d) {
                print_r($result['results']->toArray());
            } 
        }
    }

    /**
     * search for duplicates
     * 
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param  Tinebase_Model_Filter_FilterGroup
     * @param string $_field
     * @return array with ids / field
     * 
     * @todo add more options (like soundex, what do do with duplicates/delete/merge them, ...)
     */
    protected function _searchDuplicates(Tinebase_Controller_Record_Abstract $_controller, $_filter, $_field)
    {
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 100,
        ));
        $results = array();
        $allRecords = array();
        $totalCount = $_controller->searchCount($_filter);
        echo 'Searching ' . $totalCount . " record(s) for duplicates\n";
        while ($pagination->start < $totalCount) {
            $records = $_controller->search($_filter, $pagination);
            foreach ($records as $record) {
                if (in_array($record->{$_field}, $allRecords)) {
                    $allRecordsFlipped = array_flip($allRecords);
                    $duplicateId = $allRecordsFlipped[$record->{$_field}];
                    $results[] = array('id' => $duplicateId, 'value' => $record->{$_field});
                    $results[] = array('id' => $record->getId(), 'value' => $record->{$_field});
                }
                
                $allRecords[$record->getId()] = $record->{$_field};
            }
            $pagination->start += 100;
        }
        
        return $results;
    }
}
