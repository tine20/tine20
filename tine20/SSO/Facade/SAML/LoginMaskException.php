<?php declare(strict_types=1);
/**
 * Render Login Mask Exception class for simpleSAMLphp
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class SSO_Facade_SAML_LoginMaskException extends \SimpleSAML\Error\Exception
{
    public function __construct()
    {
        parent::__construct('');
    }
}
