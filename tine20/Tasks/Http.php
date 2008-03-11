<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the calendar application
 * 
 * @package Tasks
 */
class Tasks_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Tasks';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            self::_appendFileTime("Tasks/js/Status.js"),
            self::_appendFileTime("Tasks/js/Tasks.js"),
        );
    }

    
    /**
     * Returns initial data which is send to the app at createon time.
     *
     * When the mainScreen is created, Tinebase_Http_Controler queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial datas are just javascript varialbes declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * varialbe names get prefixed with Tine.<applicationname>
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getInitialMainScreenData()
    {
        $controller = Tasks_Controller::getInstance();
        $initialData = array(
            'AllStati' => $controller->getStati(),
            'DefaultContainer' => $controller->getDefaultContainer()
        );
        
        foreach ($initialData as &$data) {
            $data->setTimezone(Zend_Registry::get('userTimeZone'));
            $data = $data->toArray();
        }
        return $initialData;    
    }

    /**
     * Supplies HTML for edit tasks dialog
     * 
     * @param int    $taskId
     * @param string $linkingApp
     * @param int    $linkedId
     */
    public function editTask($taskId=-1, $linkingApp='', $linkedId=-1)
    {
        if($taskId >= 0) {
            $tjs = new Tasks_Json();
            $task = Zend_Json::encode($tjs->getTask($taskId));
        } else {
        	// prepare initial data (temporary, this should become part of json interface)
        	$newTask = new Tasks_Model_Task(array(), true);
        	$newTask->container = Zend_Json::encode(Tasks_Controller::getInstance()->getDefaultContainer($linkingApp)->toArray());
        	
        	$task = Zend_Json::encode($newTask->toArray());
        }
        
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
		$view->formData['linking']['link_app1'] = $linkingApp;
		$view->formData['linking']['link_id1'] = $linkedId;		
		
        $view->title="edit task";
        
        $view->jsIncludeFiles  = $this->getJsFilesToInclude();
        $view->cssIncludeFiles = $this->getCssFilesToInclude();
        $view->initialData = array('Tasks' => $this->getInitialMainScreenData());

        $view->jsExecute = 'Tine.Tasks.EditDialog(' . $task . ');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $view->jsIncludeFiles);
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
}