<?php declare(strict_types=1);

/**
 * facade for simpleSAMLphp Auth\SourceFactory class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_SAML_AuthSourceFactory implements \SimpleSAML\Auth\SourceFactory
{

    public function create(array $info, array $config)
    {
        // TODO: Implement create() method.
        return new SSO_Facade_SAML_AuthSource($info, $config);
    }
}
