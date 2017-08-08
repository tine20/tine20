<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Auth_SecondFactor_PrivacyIdea extends Tinebase_Auth_SecondFactor_Abstract
{
    /**
     * validate second factor
     *
     * @param string $username
     * @param string $password
     * @param boolean $allowEmpty
     * @return integer (Tinebase_Auth::FAILURE|Tinebase_Auth::SUCCESS)
     */
    public function validate($username, $password, $allowEmpty = false)
    {
        if (! $allowEmpty && empty($password)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Empty password given');
            return Tinebase_Auth::FAILURE;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Options: ' . print_r($this->_options, true));

        $url = isset($this->_options['url']) ? $this->_options['url'] : 'https://localhost/validate/check';

        $adapter = new Zend_Http_Client_Adapter_Socket();

        $adapter->setStreamContext($this->_options = array(
            'ssl' => array(
                // Verify server side certificate by default
                'verify_peer'       => isset($this->_options['ignorePeerName'])  ? false : true,
                // do not accept invalid or self-signed SSL certificates by default
                'allow_self_signed' => isset($this->_options['allowSelfSigned']) ? true  : false
            )
        ));

        $client = new Zend_Http_Client($url, array(
            'maxredirects' => 0,
            'timeout'      => 30
        ));
        $client->setAdapter($adapter);
        $params = array(
            'user' => $username,
            'pass' => $password
        );
        $client->setParameterPost($params);

        try {
            $response = $client->request(Zend_Http_Client::POST);
        } catch (Zend_Http_Client_Adapter_Exception $zhcae) {
            Tinebase_Exception::log($zhcae);
            return Tinebase_Auth::FAILURE;
        }

        $body = $response->getBody();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Request: ' . $client->getLastRequest());
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Response: ' . $body);

        if ($response->getStatus() !== 200) {
            return Tinebase_Auth::FAILURE;
        }

        $result = Tinebase_Helper::jsonDecode($body);

        if (isset($result['result']) && $result['result']['status'] === true && $result['result']['value'] === true) {
            return Tinebase_Auth::SUCCESS;
        } else {
            return Tinebase_Auth::FAILURE;
        }
    }
}
