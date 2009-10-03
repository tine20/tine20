<?php
/**
 * Asterisk_Voicemail controller for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk_Voicemail controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Asterisk_Voicemail extends Voipmanager_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Asterisk_Voicemail
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Voipmanager_Model_Asterisk_Voicemail';
        $this->_backend     = new Voipmanager_Backend_Asterisk_Voicemail();
    }
        
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
            
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_Asterisk_Voicemail
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Asterisk_Voicemail();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Controller/Record/Tinebase_Controller_Record_Abstract#create($_record)
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $result =  parent::create($_record);
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $this->publishConfiguration();
        }
        
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase/Controller/Record/Tinebase_Controller_Record_Abstract#delete($_ids)
     */
    public function delete($_ids)
    {
        $result = parent::delete($_ids);
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $this->publishConfiguration();
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Controller/Record/Tinebase_Controller_Record_Abstract#update($_record)
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $result =  parent::update($_record);
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update voicemail configuration');
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $this->publishConfiguration();
        }
        
        return $result;
    }
    
    /**
     * create voicemail.conf and upload to asterisk server
     * 
     * @return void
     */
    public static function publishConfiguration()
    {   
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' publish voicemail configuration');
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $asteriskConfig = Tinebase_Core::getConfig()->asterisk;
            
            $url        = $asteriskConfig->managerbaseurl;
            $username   = $asteriskConfig->managerusername;
            $password   = $asteriskConfig->managerpassword;
        } else {
            throw new Voipmanager_Exception_NotFound('can\'t publish configuration. No settings found for asterisk backend in config file!');
        }

        $filter = new Voipmanager_Model_Asterisk_ContextFilter();
        $contexts = Voipmanager_Controller_Asterisk_Context::getInstance()->search($filter);
        
        $fp = fopen("php://temp", 'r+');
        
        foreach($contexts as $context) {
            $filter = new Voipmanager_Model_Asterisk_VoicemailFilter(array(
                array(
                    'field'     => 'context_id',
                    'operator'  => 'equals',
                    'value'     => $context->getId()
                )
            ));
            $voicemails = Voipmanager_Controller_Asterisk_Voicemail::getInstance()->search($filter);

            if(count($voicemails) == 0) {
                continue;
            }

            fputs($fp, "[" . $context->name . "]\n");
            
            foreach($voicemails as $voicemail) {
                fputs($fp, sprintf("%s = %s,%s,%s\n",
                    $voicemail->mailbox,
                    $voicemail->password,
                    $voicemail->fullname,
                    $voicemail->email
                ));
            }
            fputs($fp, "\n");
        }
        
        rewind($fp);
        
        $ajam = new Ajam_Connection($url);
        $ajam->login($username, $password);
        $ajam->upload($url . '/tine20config', 'voicemail.conf', stream_get_contents($fp));
        $ajam->command('voicemail reload');
        $ajam->logout();
    }
}
