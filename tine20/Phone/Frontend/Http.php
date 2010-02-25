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
class Phone_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Phone';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Phone/js/Models.js',
            'Phone/js/Phone.js',
        );
    }
    
    /**
     * create edit MyPhone dialog
     *
     * @param int $phoneId
     * 
     * @todo remove that when new myphone edit dialog is working
     * @deprecated
     */
    public function editMyPhone($phoneId = NULL)
    {
        $currentAccount = Tinebase_Core::getUser()->toArray();
        
        if (!empty($phoneId)) {
            $snomPhone = Voipmanager_Controller_MyPhone::getInstance()->getMyPhone($phoneId, $currentAccount['accountId']);
            //unset($phone->lines);

            $_phoneData = $snomPhone->toArray();
            $_phoneSettingsData = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($_phoneData['id'])->toArray();
            
            $_templateData = Voipmanager_Controller_Snom_Template::getInstance()->get($_phoneData['template_id'])->toArray();            
            $_settingsData = Voipmanager_Controller_Snom_Setting::getInstance()->get($_templateData['setting_id'])->toArray();
            
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
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_phoneData, true));
            
            // encode the data arrays
            $encodedSnomPhone = Zend_Json::encode($_phoneData);
        } else {
            //$phone = new Voipmanager_Model_Snom_Phone();
            //$lines = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_Line');
            $encodedWritable = '{}';
            $encodedSnomPhone = '{}';            
            //$encodedSettings = '{}';
        }
       
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit myPhone data";
        $view->jsExecute = 'Tine.Voipmanager.MyPhones.EditDialog.display(' . $encodedSnomPhone . ', '. $encodedWritable .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('jsclient.php');
    }    
}
