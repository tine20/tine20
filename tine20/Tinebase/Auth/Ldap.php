<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * LDAP authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_Ldap extends Zend_Auth_Adapter_Ldap implements Tinebase_Auth_Interface
{
    protected $resolveIdentityFromEmailToLogin = false;

    /**
     * Constructor
     *
     * @param array  $options An array of arrays of Zend_Ldap options
     * @param string $username
     * @param string $password
     */
    public function __construct(array $options = array(),  $username = null, $password = null)
    {
        $this->setOptions($options);
        if ($username !== null) {
            $this->setIdentity($username);
        }
        if ($password !== null) {
            $this->setCredential($password);
        }
    }
    
    /**
     * Returns the LDAP Object
     *
     * @return Tinebase_Ldap The Tinebase_Ldap object used to authenticate the credentials
     */
    public function getLdap()
    {
        if ($this->_ldap === null) {
            /**
             * @see Tinebase_Ldap
             */
            $this->_ldap = new Tinebase_Ldap($this->getOptions());
        }
        return $this->_ldap;
    }
    
    /**
     * set login name
     *
     * @param string $_identity
     * @return Tinebase_Auth_Ldap
     */
    public function setIdentity($_identity)
    {
        if ($this->resolveIdentityFromEmailToLogin) {
            // Throw Exception if filter does not work to change filter below
            Tinebase_Model_Filter_FilterGroup::$beStrict = true;
            try {
                // Maybe Tinebase_User is not assembled finally during ldap auth (issue #7418)
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_FullUser::class, [
                    ['field' => 'accountEmailAddress', 'operator' => 'equals', 'value' => $_identity]
                ]);
            }
            catch (Tinebase_Exception_Record_DefinitionFailure $e) {
                // If field 'accountEmailAddress' throws try 'email' instead
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_FullUser::class, [
                    ['field' => 'email', 'operator' => 'equals', 'value' => $_identity]
                ]);
            }
            catch (Exception $e) {
                // suppress Exception handling in Tine log for this defined case
            }
            Tinebase_Model_Filter_FilterGroup::$beStrict = false;

            if($user = Tinebase_User::getInstance()->search($filter)->getFirstRecord()) {
                $_identity = $user->accountLoginName;
            }
        }
        parent::setUsername($_identity);
        return $this;
    }
    
    /**
     * set password
     *
     * @param string $_credential
     * @return Tinebase_Auth_Ldap
     */
    public function setCredential($_credential)
    {
        parent::setPassword($_credential);
        return $this;
    }

    /**
     * @return bool
     */
    public function supportsAuthByEmail()
    {
        return true;
    }

    /**
     * @return self
     */
    public function getAuthByEmailBackend()
    {
        $this->resolveIdentityFromEmailToLogin = true;
        return $this;
    }
}
