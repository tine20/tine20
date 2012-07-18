<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
     * _authenticateValidateResult() - This method attempts to validate that the record in the
     * result set is indeed a record that matched the identity provided to this adapter.
     *
     * @param array $resultIdentity
     * @return Zend_Auth_Result
     */
    protected function _authenticateValidateResult($resultIdentity)
    {
        if (empty($resultIdentity[$this->_credentialColumn])) {
            $validatedPw = ($this->_credential === '');
        } else {
            $passwordHash = substr($resultIdentity[$this->_credentialColumn], 0, 1) === '{' 
                ? $resultIdentity[$this->_credentialColumn] 
                : '{PLAIN-MD5}' . $resultIdentity[$this->_credentialColumn];
            $validatedPw = Hash_Password::validate($passwordHash, $this->_credential);
        }
        
        if ($validatedPw !== TRUE) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->_authenticateCreateAuthResult();
        }

        unset($resultIdentity['zend_auth_credential_match']);
        $this->_resultRow = $resultIdentity;

        $this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
        $this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->_authenticateCreateAuthResult();
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
