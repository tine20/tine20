<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Http_Server to handle Asterisk realtime sipregs curl backend
 *
 * @package     Voipmanager
 */
class Voipmanager_Frontend_Asterisk_MeetMe
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';

    /**
     * handle request for single line
     *
     * @param string $name
     */
    public function handleSingle($name)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' handle single request for ' . print_r($_REQUEST, true));
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' handle single request for ' . $name);
                
        $filter = new Voipmanager_Model_Asterisk_MeetmeFilter(array(
            array(
                'field'     => 'confno',
                'operator'  => 'equals',
                'value'     => $name
            )
        ));
        $conferences = Voipmanager_Controller_Asterisk_Meetme::getInstance()->search($filter);
        
        if(count($conferences) == 0) {
            Zend_Registry::get('logger')->warn(__METHOD__ . '::' . __LINE__ . ' no conference(meetme) found confno: ' . $name);
            return;
        }
        
        $conference = $conferences[0];

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found conference ' . print_r($conference->toArray(), true));

        $skipItems = array('id', 'members');
        
        $resultParts = array();
        
        foreach($conference as $key => $value) {
            if(in_array($key, $skipItems)) {
                continue;
            }
            $resultParts[] = $key . '=' . urlencode($value);  
        }
        
        $result = implode('&', $resultParts);
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' result ' . $result);
        
        echo $result . "\n";
    }
    
    /**
     * handle update for sip registration
     *
     * @param string $confno
     */
    public function handleUpdate($confno)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " handle update request " . print_r($_REQUEST, true));
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " handle update request for " . $confno);
        
        $valuesToUpdate = $_POST;
        
        $filter = new Voipmanager_Model_Asterisk_MeetmeFilter(array(
            array(
                'field'     => 'confno',
                'operator'  => 'equals',
                'value'     => $confno
            )
        ));
        $conferences = Voipmanager_Controller_Asterisk_Meetme::getInstance()->search($filter);
        
        if(count($conferences) == 0) {
            Zend_Registry::get('logger')->warn(__METHOD__ . '::' . __LINE__ . ' conference not found confno: ' . $confno);
            return;
        }
        
        $conference = $conferences[0];

        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found sip peer ' . print_r($sipPeer->toArray(), true));

        foreach($valuesToUpdate as $key => $value) {
            if($conference->has($key)) {
                $conference->$key = $value;
            }
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update conference ' . print_r($conference->toArray(), true));

        try {
            Voipmanager_Controller_Asterisk_Meetme::getInstance()->update($conference);
        } catch (Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update failed' . $e->getMessage());
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update failed' . $e->getTraceAsString());
        }
        
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update done');
        
        echo "1\n";
    }
    
    /**
     * handle require
     * 
     * just return 0 as we support all needed fields
     * 
     * [name] => char:10ipaddr=char:15port=uinteger2:5regseconds=integer4:11defaultuser=char:10fullcontact=char:35regserver=char:20useragent=char:20lastms=integer4:11
     *
     */
    public function handleRequire()
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' $_REQUEST = ' . print_r($_REQUEST, true));
        
        echo "0\n";
    }    
}