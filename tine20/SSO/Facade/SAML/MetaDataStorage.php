<?php declare(strict_types=1);

/**
 * facade for simpleSAMLphp MetaDataStorage class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_SAML_MetaDataStorage extends \SimpleSAML\Metadata\MetaDataStorageSource
{

    public function getMetaData($index, $set)
    {
        static $cache = [];

        if (isset($cache[$set][$index])) {
            return $cache[$set][$index];
        }

        switch ($set) {
            // the SP aka relyingparty config
            case 'saml20-sp-remote': /*
                $rp = SSO_Controller_RelyingParty::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_RelyingParty::class, [
                        ['field' => SSO_Model_RelyingParty::FLD_NAME, 'operator' => 'equals', 'value' => $index],
                        ['field' => SSO_Model_RelyingParty::FLD_CONFIG_CLASS, 'operator' => 'equals', 'value' =>
                            SSO_Model_Saml2RPConfig::class],
                    ]))->getFirstRecord();
                if (!$rp) {
                    $result = [];
                } else {
                    $result = $rp->{SSO_Model_RelyingParty::FLD_CONFIG}->getSaml2Array();
                }*/
                $result = [
                    'AssertionConsumerService' => [
                        'Location' => 'https://localhost:8443/auth/saml2/sp/saml2-acs.php/localhost',
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    ],
                    'IDPList' => [ /* add us, aka IDP */],
                    'entityid' => 'https://localhost:8443/auth/saml2/sp/metadata.php',
                    'attributeencodings' => [
                        'eduPersonPrincipalName' => 'string',
                    ],
                    'ForceAuthn' => true,
                ];
                $cache[$set][$index] = $result;
                return $result;
                
            default:
                // this is the IDP config
                return [
                    'auth' => 'tine20',
                    'entityid' => 'tine20',
                    'privatekey' => dirname(dirname(__DIR__)) . '/keys/saml2.pem',
                    'certificate' => dirname(dirname(__DIR__)) . '/keys/saml2.crt',
                    //'validate.authnrequest' => true, // TODO FIXME enable this, add certs to aboves SP config
                ];
        }
    }
}
