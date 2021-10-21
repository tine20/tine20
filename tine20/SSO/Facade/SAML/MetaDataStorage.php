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
            case 'saml20-sp-remote':
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
                }
                $cache[$set][$index] = $result;
                return $result;
                
            default:
                // this is the IDP config
                $saml2Config = SSO_Config::getInstance()->{SSO_Config::SAML2};
                return [
                    'NameIDFormat' => \SAML2\Constants::NAMEID_PERSISTENT,
                    'simplesaml.nameidattribute' => 'uid',
                    'auth' => 'tine20',
                    'entityid' => $saml2Config->{SSO_Config::SAML2_ENTITYID},
                    'privatekey' => $saml2Config->{SSO_Config::SAML2_KEYS}[0]['privatekey'],
                    'certificate' => $saml2Config->{SSO_Config::SAML2_KEYS}[0]['certificate'],
                    //'validate.authnrequest' => true, // TODO FIXME enable this, add certs to aboves SP config
                ];
        }
    }
}
