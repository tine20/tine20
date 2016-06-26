<?php
/**
 * ActiveDirectory generic trait
 *
 * @package     Tinebase
 * @subpackage  ActiveDirectory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ActiveDirectory trait for reading domain configuration
 * - can be used by User/Group AD controllers
 *
 * @package     Tinebase
 * @subpackage  ActiveDirectory
 *
 */
trait Tinebase_ActiveDirectory_DomainConfigurationTrait
{
    /**
     * AD domain config
     *
     * @var array
     */
    protected $_domainConfig = null;

    /**
     * fetch domain config with domain sid and name
     *
     * @throws Tinebase_Exception_Backend_Ldap
     * @throws Zend_Ldap_Exception
     * @return array
     *
     * TODO cache this longer?
     */
    public function getDomainConfiguration()
    {
        if ($this->_domainConfig === null) {
            $this->_domainConfig = $this->getLdap()->search(
                'objectClass=domain',
                $this->getLdap()->getFirstNamingContext(),
                Zend_Ldap::SEARCH_SCOPE_BASE
            )->getFirst();

            $this->_domainConfig['domainSidBinary'] = $this->_domainConfig['objectsid'][0];
            $this->_domainConfig['domainSidPlain'] = Tinebase_Ldap::decodeSid($this->_domainConfig['objectsid'][0]);

            $domainNameParts = array();
            $keys = null; // not really needed
            Zend_Ldap_Dn::explodeDn($this->_domainConfig['distinguishedname'][0], $keys, $domanNameParts);
            $this->_domainConfig['domainName'] = implode('.', $domainNameParts);
        }

        return $this->_domainConfig;
    }
}
