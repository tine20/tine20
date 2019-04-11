<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */
class Tinebase_Auth_OpenIdConnectMock extends Tinebase_Auth_OpenIdConnect
{
    protected function _getClient()
    {
        if ($this->_client === null) {
            return new Tinebase_Auth_OpenIdConnectMockClient();
        }

        return $this->_client;
    }
}
