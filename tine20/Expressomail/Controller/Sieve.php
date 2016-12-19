<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add __destruct with $_backend->logout()?
 */

/**
 * Sieve controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_Sieve extends Tinebase_Controller_Abstract
{
    /**
     * script name
     *
     * @var string
     */
    protected $_scriptName = 'expressoV3';

    /**
     * old script name (this is used to read filter settings from from egw for example and save them with the new name)
     * 
     * @var string
     */
    protected $_oldScriptName = '';
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';
    
    /**
     * Sieve server backend
     *
     * @var Expressomail_Backend_Sieve
     */
    protected $_backend = NULL;
    
    /**
     * Sieve script data backend
     *
     * @var string
     * 
     * @todo create factory class?
     */
    protected $_scriptDataBackend = 'Sql';
    //protected $_scriptDataBackend = 'Script';
    
    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_Sieve
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
           $user = Tinebase_Core::getUser()->toArray(); 
           $this->_oldScriptName = $user['accountLoginName'];

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
     * @return Expressomail_Controller_Sieve
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_Sieve();
        }
        
        return self::$_instance;
    }
    
    /**
     * Get the allowed domais to a sieve redirect
     *
     * @return array
     */
    public function getAllowedSieveRedirectDomains(){
        $sieveConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SIEVE, new Tinebase_Config_Struct())->toArray();
        if($sieveConfig['limitDomains'] == 1){
            $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
            $domains = $smtpConfig['primarydomain'];
            $domains .= (array_key_exists('secondarydomains', $smtpConfig))&&$smtpConfig['secondarydomains']  ? ',' . $smtpConfig['secondarydomains'] : '';
            $domains .= (array_key_exists('obligatorydomains', $smtpConfig))&&$smtpConfig['obligatorydomains']  ? ',' . $smtpConfig['obligatorydomains'] : '';
            $domains .= $sieveConfig['allowedDomains'] != ''?','.$sieveConfig['allowedDomains']:'';
            $domains = strtolower($domains);
            $domains = str_replace(' ', '', $domains);
            //return explode(',',$domains);
            return $domains;
        }else{
            return '';
        }

    }
    /**
     * get vacation for account
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @return Expressomail_Model_Sieve_Vacation
     */
    public function getVacation($_accountId)
    {
        $script = $this->_getSieveScript($_accountId);
        $vacation = ($script !== NULL) ? $script->getVacation() : NULL;
        
        $result = new Expressomail_Model_Sieve_Vacation(array(
            'id'    => ($_accountId instanceOf Expressomail_Model_Account) ? $_accountId->getId() : $_accountId
        ));
            
        if ($vacation !== NULL) {
            $result->setFromFSV($vacation);
        }
        
        return $result;
    }
    
    /**
     * get sieve script for account
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @return NULL|Expressomail_Sieve_Backend_Abstract
     */
    protected function _getSieveScript($_accountId)
    {
        $script = NULL;
        if ($this->_scriptDataBackend === 'Sql') {
            try {
                $script = new Expressomail_Sieve_Backend_Sql($_accountId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $serverScript = $this->_getServerSieveScript($_accountId);
                if ($serverScript !== NULL) {
                    $script = $this->_createNewSieveScript($_accountId, $serverScript);
                }
            }
        } else {
            $script = $this->_getServerSieveScript($_accountId);
        }
        
        return $script;
    }
    
    /**
     * get sieve script from sieve server
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @return NULL|Expressomail_Sieve_Backend_Script
     */
    protected function _getServerSieveScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $result = NULL;
        $scripts = $this->_backend->listScripts();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting list of SIEVE scripts: ' . print_r($scripts, TRUE));
   
        foreach (array($this->_scriptName, $this->_oldScriptName) as $scriptNameToFetch) {
            if (count($scripts) > 0 && array_key_exists($scriptNameToFetch, $scripts)) {
                $scriptName = $scripts[$scriptNameToFetch]['name'];
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Get SIEVE script: ' . $scriptName);
                
                $script = $this->_backend->getScript($scriptName);
                if ($script) {
                    if ($scriptNameToFetch == $this->_oldScriptName) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got old SIEVE script for migration.');
                    }
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Got SIEVE script: ' . $script);
                    return new Expressomail_Sieve_Backend_Script($script);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Could not get SIEVE script: ' . $scriptName);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No relevant SIEVE scripts found.');
            }
        }
        
        return $result;
    }
    
    /**
     * init and connect to sieve backend + authenticate with imap user of account
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @throws Expressomail_Exception
     */
    protected function _setSieveBackendAndAuthenticate($_accountId)
    {
        if (empty($_accountId)) {
            throw new Expressomail_Exception('No account id given.');
        }
        
        $this->_backend = Expressomail_Backend_SieveFactory::factory($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' sieve server capabilities: ' . print_r($this->_backend->capability(), TRUE));
    }
    
    /**
     * set vacation for account
     * 
     * @param Expressomail_Model_Sieve_Vacation $_vacation
     * @return Expressomail_Model_Sieve_Vacation
     * @throws Tinebase_Exception_AccessDenied
     */
    public function setVacation(Expressomail_Model_Sieve_Vacation $_vacation)
    {
        $account = Expressomail_Controller_Account::getInstance()->get($_vacation->getId());
        if ($account->user_id !== Tinebase_Core::getUser()->getId()) {
            throw new Tinebase_Exception_AccessDenied('It is not allowed to set the vacation message of another user.');
        }
        
        $this->_setSieveBackendAndAuthenticate($account);
        $this->_addVacationUserData($_vacation, $account);
        $this->_checkCapabilities($_vacation);
        $this->_addVacationSubject($_vacation);
        
        $fsv = $_vacation->getFSV();
        
        $script = $this->_getSieveScript($account);
        if ($script === NULL) {
            $script = $this->_createNewSieveScript($account);
        }
        $script->setVacation($fsv);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated vacation SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($account, $script);
        Expressomail_Controller_Account::getInstance()->setVacationActive($account, $_vacation->enabled);
        
        return $this->getVacation($account);
    }
    
    /**
     * add addresses and from to vacation
     * 
     * @param Expressomail_Model_Sieve_Vacation $_vacation
     * @param Expressomail_Model_Account $_account
     */
    protected function _addVacationUserData(Expressomail_Model_Sieve_Vacation $_vacation, Expressomail_Model_Account $_account)
    {
        if ($_account->type == Expressomail_Model_Account::TYPE_SYSTEM) {
            $addresses = $this->_getSystemAccountVacationAddresses($_account);
        } else {
            $addresses = array($_account->email);
        }
        $_vacation->addresses = $addresses;
        
        if (! $this->_isDbmailSieve()) {
            // and: no from for dbmail vacations
            $from = $_account->from;
            if (strpos($from, '@') === FALSE) {
                $from .= ' <' . $_account->email . '>';
            }
            $_vacation->from = $from;
        }
    }
    
    /**
     * get vacation addresses from system account
     * 
     * @param Expressomail_Model_Account $account
     * @return array
     */
    protected function _getSystemAccountVacationAddresses(Expressomail_Model_Account $account)
    {
        $addresses = array();
        $fullUser = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        
        $addresses[] = (! empty(Tinebase_Core::getUser()->accountEmailAddress)) ? Tinebase_Core::getUser()->accountEmailAddress : $account->email;
        if ($fullUser->smtpUser && ! empty($fullUser->smtpUser->emailAliases)) {
            $addresses = array_merge($addresses, $fullUser->smtpUser->emailAliases);
        }
        
        // append all valid domains if nessesary
        $systemAccountConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        foreach ($addresses as $idx => $address) {
            if (! strpos($address, '@')) {
                $addresses[$idx] = $address . '@' . $systemAccountConfig['primarydomain'];
                if ($systemAccountConfig['secondarydomains']) {
                    foreach (explode(',', $systemAccountConfig['secondarydomains']) as $secondarydomain) {
                        if ($secondarydomain) {
                            $addresses[] = $address . '@' . trim($secondarydomain);
                        }
                    }
                }
            }
        }
        
        if ($this->_isDbmailSieve() && $fullUser->imapUser && ! empty($fullUser->imapUser->emailUID)) {
            // dbmail sieve needs dbmail uid (envelope recipient) in addresses
            // see https://bugs.launchpad.net/ubuntu/+source/libsieve/+bug/883627
            $addresses[] = $fullUser->imapUser->emailUID;
        }
        
        return $addresses;
    }
    
    /**
     * check sieve backend capabilities
     * 
     * @param Expressomail_Model_Sieve_Vacation $_vacation
     */
    protected function _checkCapabilities(Expressomail_Model_Sieve_Vacation $_vacation)
    {
        $capabilities = $this->_backend->capability();
        
        if (! in_array('mime', $capabilities['SIEVE'])) {
            unset($_vacation->mime);
            $_vacation->reason = Expressomail_Model_Message::convertHTMLToPlainTextWithQuotes($_vacation->reason);
        }
        
        if (preg_match('/cyrus/i', $capabilities['IMPLEMENTATION'])) {
            // cyrus does not support :from
            unset($_vacation->from);
        }
        
        if (in_array('date', $capabilities['SIEVE']) && in_array('relational', $capabilities['SIEVE'])) {
            $_vacation->date_enabled = TRUE;
        }
    }
    
    /**
     * add vacation subject
     * 
     * @param Expressomail_Model_Sieve_Vacation $_vacation
     */
    protected function _addVacationSubject(Expressomail_Model_Sieve_Vacation $_vacation)
    {
        if ($this->_isDbmailSieve()) {
            // dbmail seems to have problems with different subjects and sends vacation responses to the same recipients again and again
            $translate = Tinebase_Translation::getTranslation('Expressomail');
            $_vacation->subject = sprintf($translate->_('Out of Office reply from %1$s'), Tinebase_Core::getUser()->accountFullName);
        }
    }
    
    /**
     * checks if sieve implementation is dbmail
     * 
     * @return boolean
     */
    protected function _isDbmailSieve()
    {
        return (preg_match('/dbmail/i', $this->_backend->getImplementation()));
    }
        
    /**
     * put updated sieve script
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @param Expressomail_Sieve_Backend_Abstract $_script
     * @throws Expressomail_Exception_Sieve
     */
    protected function _putScript($_accountId, $_script)
    {
        $scriptToPut = $_script->getSieve();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $scriptToPut);
        
        try {
            $this->_setSieveBackendAndAuthenticate($_accountId);
            $this->_backend->putScript($this->_scriptName, $scriptToPut);
            $this->activateScript($_accountId);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getTraceAsString());
            throw new Expressomail_Exception_SievePutScriptFail($zmpe->getMessage());
        }
    }

    /**
     * set sieve script name
     * 
     * @param string $_name
     */
    public function setScriptName($_name)
    {
        $this->_scriptName = $_name;
    }
    
    /**
     * delete sieve script
     * 
     * @param string|Expressomail_Model_Account $_accountId
     */
    public function deleteScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete SIEVE script ' . $this->_scriptName);
        
        $this->_backend->deleteScript($this->_scriptName);
        
        if ($this->_scriptDataBackend === 'Sql') {
            $script = new Expressomail_Sieve_Backend_Sql($_accountId, FALSE);
            $script->delete();
        }
    }

    /**
     * activate sieve script
     * 
     * @param string|Expressomail_Model_Account $_accountId
     */
    public function activateScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Activate vacation SIEVE script ' . $this->_scriptName);
        $this->_backend->setActive($this->_scriptName);
    }
    
    /**
     * get name of active script for account
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @return string|NULL
     */
    public function getActiveScriptName($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $scripts = $this->_backend->listScripts();
        
        $result = NULL;
        foreach ($scripts as $scriptname => $values) {
            if ($values['active'] == 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found active script: ' . $scriptname);
                $result = $scriptname;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Inactive script: ' . $scriptname);
            }
        }
        
        return $result;
    }
    
    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Expressomail_Model_Sieve_Rule
     */
    public function getRules($_accountId)
    {
        $result = new Tinebase_Record_RecordSet('Expressomail_Model_Sieve_Rule');
        
        $script = $this->_getSieveScript($_accountId);
        if ($script !== NULL) {
            foreach ($script->getRules() as $fsr) {
                $rule = new Expressomail_Model_Sieve_Rule();
                $rule->setFromFSR($fsr);
                $result->addRecord($rule);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Sieve script empty or could not parse it.');
        }
        
        return $result;
    }
    
    /**
     * set rules for account
     * 
     * @param string|Expressomail_Model_Account $_accountId $_accountId
     * @param Tinebase_Record_RecordSet $_rules (Expressomail_Model_Sieve_Rule)
     * @return Tinebase_Record_RecordSet
     */
    public function setRules($_accountId, Tinebase_Record_RecordSet $_rules)
    {
        $script = $this->_getSieveScript($_accountId);
        
        if ($script === NULL) {
            $script = $this->_createNewSieveScript($_accountId);
        } else {
            $script->clearRules();
        }
        
        foreach ($_rules as $rule) {
            $this->_checkRule($rule, $_accountId);
            $fsr = $rule->getFSR();
            $script->addRule($fsr);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated rules SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($_accountId, $script);
        
        return $this->getRules($_accountId);
    }
    
    /**
     * check the rules
     * 
     * @param Expressomail_Model_Sieve_Rule $_rule
     * @param string|Expressomail_Model_Account $_accountId
     * @throws Expressomail_Exception_Sieve
     */
    protected function _checkRule($_rule, $_accountId)
    {
        $account = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($_accountId);
        if ($_rule->action_type === Expressomail_Sieve_Rule_Action::REDIRECT && $_rule->enabled) {
            if ($account->email === $_rule->action_argument) {
                throw new Expressomail_Exception_Sieve('It is not allowed to redirect emails to self (' . $account->email . ')! Please change the recipient.');
            }
            $domains = $this->getAllowedSieveRedirectDomains();
            if($domains){
                $domain = substr($_rule->action_argument, strpos($_rule->action_argument, '@')+1);
                $domains = str_replace(' ','',$domains);
                $domains = str_replace('.','\\.',$domains);
                $domains = str_replace(',','|',$domains);
                if(preg_match('/'.$domains.'/', $domain) != 1){
                    throw new Expressomail_Exception_Sieve('It is not allowed to redirect to this domain  '.$_rule->action_argument);
                }
            }
        }
    }
    
    /**
     * create new sieve script for the configured backend
     * 
     * @param string|Expressomail_Model_Account $_accountId
     * @param Expressomail_Sieve_Backend_Abstract $_copyScript
     * @return Expressomail_Sieve_Backend_Abstract
     */
    protected function _createNewSieveScript($_accountId, $_copyScript = NULL)
    {
        if ($this->_scriptDataBackend === 'Sql') {
            $script = new Expressomail_Sieve_Backend_Sql($_accountId, FALSE);
        } else {
            $script = new Expressomail_Sieve_Backend_Script();
        }
        
        if ($_copyScript !== NULL) {
            $script->getDataFromScript($_copyScript);
        }
        
        return $script;
    }
    
    /**
    * get vacation message defined by template / do substitutions for dates and representative
    *
    * @param Expressomail_Model_Sieve_Vacation $vacation
    * @return string
    */
    public function getVacationMessage(Expressomail_Model_Sieve_Vacation $vacation)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($vacation->toArray(), TRUE));

        $message = $this->_getMessageFromTemplateFile($vacation->template_id);
        $message = $this->_doMessageSubstitutions($vacation, $message);
        
        return $message;
    }
    
    /**
     * get vacation message from template file
     * 
     * @param string $templateId
     * @return string
     * 
     * @todo generalize and move to Tinebase_FileSystem / Node controller
     */
    protected function _getMessageFromTemplateFile($templateId)
    {
        $template = Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $templateId)
        )))->getFirstRecord();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($template->toArray(), TRUE));
        
        $templateContainer = Tinebase_Container::getInstance()->getContainerById(Expressomail_Config::getInstance()->{Expressomail_Config::VACATION_TEMPLATES_CONTAINER_ID});
        $path = Tinebase_FileSystem::getInstance()->getContainerPath($templateContainer) . '/' . $template->name;
        
        $templateHandle = Tinebase_FileSystem::getInstance()->fopen($path, 'r');
        $message = stream_get_contents($templateHandle);
        Tinebase_FileSystem::getInstance()->fclose($templateHandle);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $message);
        
        return $message;
    }
    
    /**
     * do substitutions in vacation message
     * 
     * @param Expressomail_Model_Sieve_Vacation $vacation
     * @param string $message
     * @return string
     * 
     * @todo get locale from placeholder (i.e. endDate-_LOCALESTRING_)
     * @todo get field from placeholder (i.e. representation-_FIELDNAME_)
     * @todo use html templates?
     * @todo use ZF templates / phplib tpl files?
     */
    protected function _doMessageSubstitutions(Expressomail_Model_Sieve_Vacation $vacation, $message)
    {
        $timezone = Tinebase_Core::getUserTimezone();
        $representatives = ($vacation->contact_ids) ? Addressbook_Controller_Contact::getInstance()->getMultiple($vacation->contact_ids) : array();
        if ($vacation->contact_ids && count($representatives) > 0) {
            // sort representatives
            $representativesArray = array();
            foreach ($vacation->contact_ids as $id) {
                $representativesArray[] = $representatives->getById($id);
            }
        }
        try {
            $ownContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            $ownContact = NULL;
        }

        $search = array(
            '{startDate-en_US}',
            '{endDate-en_US}',
            '{startDate-de_DE}',
            '{endDate-de_DE}',
            '{representation-n_fn-1}',
            '{representation-n_fn-2}',
            '{representation-email-1}',
            '{representation-email-2}',
            '{representation-tel_work-1}',
            '{representation-tel_work-2}',
            '{owncontact-n_fn}',
            '{signature}',
        );
        $replace = array(
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->start_date, $timezone, new Zend_Locale('en_US'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->end_date, $timezone, new Zend_Locale('en_US'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->start_date, $timezone, new Zend_Locale('de_DE'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->end_date, $timezone, new Zend_Locale('de_DE'), 'date'),
            (isset($representativesArray[0])) ? $representativesArray[0]->n_fn : 'unknown person',
            (isset($representativesArray[1])) ? $representativesArray[1]->n_fn : 'unknown person',
            (isset($representativesArray[0])) ? $representativesArray[0]->email : 'unknown email',
            (isset($representativesArray[1])) ? $representativesArray[1]->email : 'unknown email',
            (isset($representativesArray[0])) ? $representativesArray[0]->tel_work : 'unknown phone',
            (isset($representativesArray[1])) ? $representativesArray[1]->tel_work : 'unknown phone',
            ($ownContact) ? $ownContact->n_fn : '',
            ($vacation->signature) ? Expressomail_Model_Message::convertHTMLToPlainTextWithQuotes(
                preg_replace("/\\r|\\n/", '', $vacation->signature)) : '',
        );
        
        $result = str_replace($search, $replace, $message);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $result);
        
        return $result;
    }
}
