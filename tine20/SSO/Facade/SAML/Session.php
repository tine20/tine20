<?php declare(strict_types=1);


/**
 * Facade for simpleSAMLphp Session class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class SSO_Facade_SAML_Session
{
    protected $data = [];
    protected $spEntityId;

    public function setSPEntityId($spEntityId)
    {
        $this->spEntityId = $spEntityId;
    }

    protected function _getData()
    {
        if (empty($this->data)) {
            $data = Tinebase_Session::getSessionNamespace(self::class)->data;
            if (!empty($data)) {
                $this->data = $data;
            }
        }
    }

    /**
     * Get the current persistent authentication state.
     *
     * @param string $authority The authority to retrieve the data from.
     *
     * @return array|null  The current persistent authentication state, or null if not authenticated.
     */
    public function getAuthState($authority)
    {
        $this->_getData();
        return isset($this->data[$authority][$this->spEntityId]) ? $this->data[$authority][$this->spEntityId] : null;
    }

    public function __call($funcName, $params)
    {
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' called undefined function ' . $funcName);
        return null;
    }

    public function doLogout($authority)
    {
        $this->_getData();

        $requests = [];
        if (isset($this->data[$authority]) && is_array($this->data[$authority])) {
            foreach ($this->data[$authority] as $spEntityId => $data) {
                if ($this->spEntityId === $spEntityId || !isset($data['SPMetadata']['SingleLogoutService']['Location']))
                    continue;

                try {
                    $lr = \SimpleSAML\Module\saml\Message::buildLogoutRequest(
                        \SimpleSAML\IdP::getById('saml2:tine20')->getConfig(),
                        ($dstCfg = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler()
                            ->getMetaDataConfig($spEntityId, 'saml20-sp-remote'))
                    );
                    if (is_object($dstCfg->getConfigItem('SingleLogoutService')) && !empty($dstCfg->getConfigItem('SingleLogoutService')->getString('Location'))) {
                        $lr->setDestination($dstCfg->getConfigItem('SingleLogoutService')->getString('Location'));
                        $nameId = new SAML2\XML\saml\NameID();
                        $nameId->setValue(Tinebase_Core::getUser()->accountLoginName);
                        $lr->setNameId($nameId);
                        $requests[] = $lr;
                    }

                } catch (Exception $e) {
                    Tinebase_Exception::log($e);
                }
            }
        }

        unset($this->data[$authority]);
        Tinebase_Session::getSessionNamespace(self::class)->data = $this->data;

        return $requests;
    }

    public function doLogin($authority, $data)
    {
        if ($data === null) {
            $data = [];
        }

        $data['Authority'] = $authority;

        if (!isset($data['AuthnInstant'])) {
            $data['AuthnInstant'] = time();
        }

        $maxSessionExpire = time() + (8 * 60 * 60);
        if (!isset($data['Expire']) || $data['Expire'] > $maxSessionExpire) {
            // unset, or beyond our session lifetime. Clamp it to our maximum session lifetime
            $data['Expire'] = $maxSessionExpire;
        }

        $this->_getData();
        $this->data[$authority][$this->spEntityId] = $data;
        Tinebase_Session::getSessionNamespace(self::class)->data = $this->data;
    }

    public function setData() {}
    public function getData() {}
    public function deleteData() {}
    public function getTrackID() {}

    // TODO fixme these two we probably want to implement...
    public function terminateAssociation() {}
    public function addAssociation() {}

    public function getAuthData($authority, $index)
    {
        $this->_getData();
        if (isset($this->data[$authority][$this->spEntityId][$index])) {
            return $this->data[$authority][$this->spEntityId][$index];
        }
        return null;
    }

    public function isValid($authority)
    {
        $this->_getData();
        return isset($this->data[$authority]);
    }
}
