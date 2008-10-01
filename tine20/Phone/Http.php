<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the phone application
 * 
 * @package Phone
 */
class Phone_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Phone';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Phone/js/Phone.js'
        );
    }
    
    /**
     * Returns initial data which is send to the app at creation time.
     *
     * When the mainScreen is created, Tinebase_Http_Controller queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial data objects are just javascript variables declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * variable names get prefixed with Tine.<applicationname>
     * 
     * - this function returns the user phones
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getInitialMainScreenData()
    {   
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__);
    
        $accountId = Zend_Registry::get('currentAccount')->getId();
        $json = new Phone_Json();
        
        $initialData = array(
            'Phones' => $json->getUserPhones($accountId)
        );
        
        return $initialData;
    }
    
    /**
     * create edit MyPhone dialog
     *
     * @param int $phoneId
     * 
     * @todo catch permission denied exceptions only
     * @todo should be obsolete when new window handling is introduced in Phone & Voipmanager
     */
    public function editMyPhone($phoneId = NULL)
    {
        $controller = Voipmanager_Controller::getInstance();

        $currentAccount = Zend_Registry::get('currentAccount')->toArray();
        
        if (!empty($phoneId)) {
            $snomPhone = $controller->getMyPhone($phoneId, $currentAccount['accountId']);
            //unset($phone->lines);

            $_phoneData = $snomPhone->toArray();
            $_phoneSettingsData = $controller->getSnomPhoneSettings($_phoneData['id'])->toArray();
            
            $_templateData = $controller->getSnomTemplate($_phoneData['template_id'])->toArray();            
            $_settingsData = $controller->getSnomSetting($_templateData['setting_id'])->toArray();
            
            $_writableFields = array('web_language','language','display_method','mwi_notification','mwi_dialtone','headset_device','message_led_other','global_missed_counter','scroll_outgoing','show_local_line','show_call_status','call_waiting');

            foreach($_writableFields AS $wField)
            {               
                $_fieldRW = $wField.'_writable';
                
                 if($_settingsData[$_fieldRW] == '0')
                 {
                     $_phoneSettingsData[$wField] = $_settingsData[$wField];
                     $_notWritable[$wField] = 'true';
                 } else {
                     if($_phoneSettingsData[$wField] === NULL) {
                         $_phoneSettingsData[$wField] = $_settingsData[$wField];                    
                     }
                     $_notWritable[$wField] = '';    
                 }
            }

            $encodedWritable = Zend_Json::encode($_notWritable);
                
            $_phoneData = array_merge($_phoneSettingsData,$_phoneData);
            
            // encode the data arrays
            $encodedSnomPhone = Zend_Json::encode($_phoneData);
        } else {
            //$phone = new Voipmanager_Model_SnomPhone();
            //$lines = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLine');
            $encodedWritable = '{}';
            $encodedSnomPhone = '{}';            
            //$encodedSettings = '{}';
        }
       
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit myPhone data";
        $view->jsExecute = 'Tine.Voipmanager.MyPhones.EditDialog.display(' . $encodedSnomPhone . ', '. $encodedWritable .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }    
}
