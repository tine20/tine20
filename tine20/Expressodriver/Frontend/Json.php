<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Expressodriver application
 *
 * @package     Expressodriver
 * @subpackage  Frontend
 */
class Expressodriver_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Expressodriver';

    /**
     * search file/directory nodes
     *
     * @param  array $filter
     * @param  array $paging
     * @return array of tree nodes
     */
    public function searchNodes($filter, $paging)
    {

        $controller = Expressodriver_Controller_Node::getInstance();
        $result = $this->_search($filter, $paging, $controller, 'Expressodriver_Model_NodeFilter');
        $this->_removeAppIdFromPathFilter($result);

        return $result;
    }

    /**
     * remove app id (base path) from filter
     *
     * @param array $_result
     *
     * @todo is this really needed? perhaps we can set the correct path in Tinebase_Model_Tree_Node_PathFilter::toArray
     */
    protected function _removeAppIdFromPathFilter(&$_result)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        foreach ($_result['filter'] as $idx => &$filter) {
            if ($filter['field'] === 'path') {
                if (is_array($filter['value'])) {
                    $filter['value']['path'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($filter['value']['path'], $app);
                } else {
                    $filter['value'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($filter['value'], $app);
                }
            }
        }
    }

    /**
     * create node
     *
     * @param array $filename
     * @param string $type directory or file
     * @param string $tempFileId
     * @param boolean $forceOverwrite
     * @return array from tree node
     */
    public function createNode($filename, $type, $tempFileId, $forceOverwrite)
    {
        $nodes = Expressodriver_Controller_Node::getInstance()->createNodes((array)$filename, $type, (array)$tempFileId, $forceOverwrite);
        $result = (count($nodes) === 0) ? array() : $this->_recordToJson($nodes->getFirstRecord());

        return $result;
    }

    /**
     * create nodes
     *
     * @param string|array $filenames
     * @param string $type directory or file
     * @param string|array $tempFileIds
     * @param boolean $forceOverwrite
     * @return array from tree nodes
     */
    public function createNodes($filenames, $type, $tempFileIds, $forceOverwrite)
    {
        $nodes = Expressodriver_Controller_Node::getInstance()->createNodes((array)$filenames, $type, (array)$tempFileIds, $forceOverwrite);

        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * copy node(s)
     *
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $forceOverwrite
     * @return array from tree nodes
     *
     * @todo: deal with copying between different adapters and controllers
     */
    public function copyNodes($sourceFilenames, $destinationFilenames, $forceOverwrite)
    {
        $nodes = Expressodriver_Controller_Node::getInstance()->copyNodes((array)$sourceFilenames, $destinationFilenames, $forceOverwrite);

        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * move node(s)
     *
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $forceOverwrite
     * @return array for tree nodes
     *
     * @todo: deal with moving between different adapters and controllers
     */
    public function moveNodes($sourceFilenames, $destinationFilenames, $forceOverwrite)
    {
        $nodes = Expressodriver_Controller_Node::getInstance()->moveNodes((array)$sourceFilenames, $destinationFilenames, $forceOverwrite);

        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * delete node(s)
     *
     * @param string|array $filenames string->single file, array->multiple
     * @return array with status
     */
    public function deleteNodes($filenames)
    {
        Expressodriver_Controller_Node::getInstance()->deleteNodes((array)$filenames);

        return array(
            'status'    => 'success'
        );
    }

    /**
     * returns the node record
     *
     * @param string $id
     * @return array with tree node
     */
    public function getNode($id)
    {
        $record = Expressodriver_Controller_Node::getInstance()->get($id);
        return $this->_recordToJson($record);
    }

    /**
     * save node
     * save node here in json fe just updates meta info (name, description, relations, customfields, tags, notes),
     * if record already exists (after it had been uploaded)
     * @param array with record data
     * @return array with tree node
     */
    public function saveNode($recordData)
    {
        if((isset($recordData['created_by']) || array_key_exists('created_by', $recordData))) {
            return $this->_save($recordData, Expressodriver_Controller_Node::getInstance(), 'Node');
        } else {    // on upload complete
            return $recordData;
        }
    }

     /**
     * Returns settings for expressodriver app
     *
     * @return  array record data
     *
     */
    public function getSettings()
    {
        $result = Expressodriver_Controller::getInstance()->getConfigSettings();

        return $result;
    }

    /**
     * creates/updates settings
     *
     * @return array created/updated settings
     */
    public function saveSettings($recordData)
    {
        $result = Expressodriver_Controller::getInstance()->saveConfigSettings($recordData);

        return $result;
    }

    /**
     * Returns registry data of Expressodriver.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData() {
        $registryData = array(
            'settings'        => $this->getSettings(),
        );
        return $registryData;
    }

    /**
     * set credentials for given adapter
     *
     * @param string $adapterName
     * @param string $password
     * @return array
     */
    public function setCredentials($adapterName, $password)
    {
        return Expressodriver_Controller::getInstance()->setCredentials($adapterName, $password);
    }


}
