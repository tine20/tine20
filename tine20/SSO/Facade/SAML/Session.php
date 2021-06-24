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
        return isset($this->data[$authority]) ? $this->data[$authority] : null;
    }

    public function __call($funcName, $params)
    {
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' called undefined function ' . $funcName);
        return null;
    }

    public function doLogout($authority)
    {
        $this->_getData();
        unset($this->data[$authority]);
        Tinebase_Session::getSessionNamespace(self::class)->data = $this->data;
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
        $this->data[$authority] = $data;
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
        if (isset($this->data[$authority][$index])) {
            return $this->data[$authority][$index];
        }
        return null;
    }

    public function isValid($authority)
    {
        $this->_getData();
        return isset($this->data[$authority]);
    }
}
