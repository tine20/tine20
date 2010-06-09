<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */ 

/**
 * SQL authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth 
 */
class Tinebase_Auth_Sql extends Zend_Auth_Adapter_DbTable implements Tinebase_Auth_Interface
{   
    const ACCTNAME_FORM_USERNAME  = 2;
    const ACCTNAME_FORM_BACKSLASH = 3;
    const ACCTNAME_FORM_PRINCIPAL = 4;
    
    
    /**
     * authenticate() - defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to authenticate '. $this->_identity);
        
        $result = parent::authenticate();
        
        if($result->isValid()) {
            // username and password are correct, let's do some additional tests
            
            if($this->_resultRow['status'] != 'enabled') {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Account: '. $this->_identity . ' is disabled');
                // account is disabled
                $authResult['code'] = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                $authResult['messages'][] = 'Account disabled.';
                return new Zend_Auth_Result($authResult['code'], $result->getIdentity(), $authResult['messages']);
            }
            
            //if(($this->_resultRow['expires_at'] !== NULL) && $this->_resultRow['expires_at'] < Zend_Date::now()->getTimestamp()) {
            if(($this->_resultRow['expires_at'] !== NULL) && Zend_Date::now()->isLater($this->_resultRow['expires_at'])) {
                // account is expired
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Account: '. $this->_identity . ' is expired');
                $authResult['code'] = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                $authResult['messages'][] = 'Account expired.';
                return new Zend_Auth_Result($authResult['code'], $result->getIdentity(), $authResult['messages']);
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->_identity . ' succeeded');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->_identity . ' failed');
        }
        
        return $result;
    }
    
    /**
     * setIdentity() - set the value to be used as the identity
     *
     * @param  string $value
     * @return Zend_Auth_Adapter_DbTable Provides a fluent interface
     */
    public function setIdentity($value)
    {
        $canonicalName = $this->getCanonicalAccountName($value);
        
        $this->_identity = $canonicalName;
        return $this;
    }
    
    /**
     * @param string $acctname The name to canonicalize
     * @param int $type The desired form of canonicalization
     * @return string The canonicalized name in the desired form
     * @throws Zend_Auth_Adapter_Exception
     */
    public function getCanonicalAccountName($acctname, $form = 0)
    {
        $this->_splitName($acctname, $dname, $uname);

        if (! $this->_isPossibleAuthority($dname)) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            throw new Zend_Auth_Adapter_Exception("Domain is not an authority for user: $acctname");
        }

        if (!$uname) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            throw new Zend_Auth_Adapter_Exception("Invalid account name syntax: $acctname");
        }

        if (function_exists('mb_strtolower')) {
            $uname = mb_strtolower($uname, 'UTF-8');
        } else {
            $uname = strtolower($uname);
        }

        if ($form === 0) {
            $form = $this->_getAccountCanonicalForm();
        }

        switch ($form) {
            case self::ACCTNAME_FORM_USERNAME:
                return $uname;
            case self::ACCTNAME_FORM_BACKSLASH:
                $accountDomainNameShort = $this->_getAccountDomainNameShort();
                if (!$accountDomainNameShort) {
                    /**
                     * @see Zend_Auth_Adapter_Exception
                     */
                    throw new Zend_Auth_Adapter_Exception('Option required: accountDomainNameShort');
                }
                return "$accountDomainNameShort\\$uname";
            case self::ACCTNAME_FORM_PRINCIPAL:
                $accountDomainName = $this->_getAccountDomainName();
                if (!$accountDomainName) {
                    /**
                     * @see Zend_Auth_Adapter_Exception
                     */
                    throw new Zend_Auth_Adapter_Exception('Option required: accountDomainName');
                }
                return "$uname@$accountDomainName";
            default:
                /**
                 * @see Zend_Auth_Adapter_Exception
                 */
                throw new Zend_Auth_Adapter_Exception("Unknown canonical name form: $form");
        }
    }
    
    /**
     * split username in domain and account name
     * 
     * @param string $name The name to split
     * @param string $dname The resulting domain name (this is an out parameter)
     * @param string $aname The resulting account name (this is an out parameter)
     */
    protected function _splitName($name, &$dname, &$aname)
    {
        $dname = null;
        $aname = $name;

        if (! Tinebase_Auth::getBackendConfiguration('tryUsernameSplit', TRUE)) {
            return;
        }

        $pos = strpos($name, '@');
        if ($pos) {
            $dname = substr($name, $pos + 1);
            $aname = substr($name, 0, $pos);
        } else {
            $pos = strpos($name, '\\');
            if ($pos) {
                $dname = substr($name, 0, $pos);
                $aname = substr($name, $pos + 1);
            }
        }
    }
    
    
    
    /**
     * @param string $dname The domain name to check
     * @return boolean
     */
    protected function _isPossibleAuthority($dname)
    {
        if ($dname === null) {
            return true;
        }
        $accountDomainName = $this->_getAccountDomainName();
        $accountDomainNameShort = $this->_getAccountDomainNameShort();
        
        if ($accountDomainName === null && $accountDomainNameShort === null) {
            return true;
        }
        if (strcasecmp($dname, $accountDomainName) == 0) {
            return true;
        }
        if (strcasecmp($dname, $accountDomainNameShort) == 0) {
            return true;
        }
        return false;
    }
    
    /**
     * @return string Either ACCTNAME_FORM_BACKSLASH, ACCTNAME_FORM_PRINCIPAL or
     * ACCTNAME_FORM_USERNAME indicating the form usernames should be canonicalized to.
     */
    protected function _getAccountCanonicalForm()
    {
        /* Account names should always be qualified with a domain. In some scenarios
         * using non-qualified account names can lead to security vulnerabilities. If
         * no account canonical form is specified, we guess based in what domain
         * names have been supplied.
         */

        $accountCanonicalForm = Tinebase_Auth::getBackendConfiguration('accountCanonicalForm', FALSE);
        if (!$accountCanonicalForm) {
            $accountDomainName = $this->_getAccountDomainName();
            $accountDomainNameShort = $this->_getAccountDomainNameShort();
            if ($accountDomainNameShort) {
                $accountCanonicalForm = self::ACCTNAME_FORM_BACKSLASH;
            } else if ($accountDomainName) {
                $accountCanonicalForm = self::ACCTNAME_FORM_PRINCIPAL;
            } else {
                $accountCanonicalForm = self::ACCTNAME_FORM_USERNAME;
            }
        }

        return $accountCanonicalForm;
    }
    
    /**
     * @return string The account domain name
     */
    protected function _getAccountDomainName()
    {
        return Tinebase_Auth::getBackendConfiguration('accountDomainName', NULL);
    }
    
    /**
     * @return string The short account domain name
     */
    protected function _getAccountDomainNameShort()
    {
        return Tinebase_Auth::getBackendConfiguration('accountDomainNameShort', NULL);
    }
}