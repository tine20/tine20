<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Generic SMS SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_MockSmsAdapter extends Tinebase_Auth_MFA_GenericSmsAdapter
{
    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        parent::__construct($_config, $id);
        $this->setHttpClientConfig([
            'adapter' => ($httpClientTestAdapter = new Tinebase_ZendHttpClientAdapter())
        ]);
        $httpClientTestAdapter->writeBodyCallBack = function($body) {
            Tinebase_Core::getLogger()->ERR(__METHOD__ . '::' . __LINE__ . ' sms request body: ' . $body);
        };
        $httpClientTestAdapter->setResponse(new Zend_Http_Response(200, []));
    }
}
