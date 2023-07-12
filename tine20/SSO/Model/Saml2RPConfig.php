<?php declare(strict_types=1);

/**
 * class to hold RelyingParty (SP) config for saml2
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold RelyingParty data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_Saml2RPConfig extends Tinebase_Record_Abstract implements SSO_RPConfigInterface
{
    public const MODEL_NAME_PART = 'Saml2RPConfig';

    public const FLD_NAME = 'name';
    public const FLD_METADATA_URL = 'metaUrl';
    public const FLD_ASSERTION_CONSUMER_SERVICE_LOCATION = 'AssertionConsumerServiceLocation';
    public const FLD_ASSERTION_CONSUMER_SERVICE_BINDING = 'AssertionConsumerServiceBinding';
    public const FLD_ENTITYID = 'entityid';
    public const FLD_SINGLE_LOGOUT_SERVICE_LOCATION = 'singleLogoutServiceLocation';
    public const FLD_SINGLE_LOGOUT_SERVICE_BINDING = 'singleLogoutServiceBinding';
    public const FLD_ATTRIBUTE_MAPPING = 'attributeMapping';
    public const FLD_CUSTOM_HOOKS = 'customHooks';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::RECORD_NAME => 'SAML2 Relying Party Config',
        self::RECORDS_NAME => 'SAML2 Relying Party Configs', // ngettext('SAML2 Relying Party Config', 'SAML2 Relying Party Configs', n)

        self::FIELDS => [
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Name', // _('Name')
            ],
            self::FLD_ENTITYID          => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::LABEL                 => 'Entity ID', // _('Entity ID')
            ],
            self::FLD_METADATA_URL      => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::LABEL                 => 'Metadata URL', // _('Metadata URL')
            ],
            self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING    => [
                self::LABEL                 => 'Assertion Consumer Service Binding', // _('Assertion Consumer Service Binding')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => SSO_Config::SAML2_BINDINGS,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION   => [
                self::LABEL                 => 'Assertion Consumer Service Location', // _('Assertion Consumer Service Location')
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_SINGLE_LOGOUT_SERVICE_BINDING    => [
                self::LABEL                 => 'Logout Service Binding', // _('Logout Service Binding')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => SSO_Config::SAML2_BINDINGS,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION   => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::LABEL                 => 'Logout Service Location', // _('Logout Service Location')
            ],
            self::FLD_ATTRIBUTE_MAPPING   => [
                self::TYPE                  => self::TYPE_JSON,
            ],
            self::FLD_CUSTOM_HOOKS        => [
                self::TYPE                  => self::TYPE_JSON,
            ],
        ]
    ];

    public function getSaml2Array(): array
    {
        $result = [
            'AssertionConsumerService' => [],
            'SingleLogoutService' => [],
            'IDPList' => [ /* add us, aka IDP */],
            'entityid' => $this->{self::FLD_ENTITYID},
            'attributeencodings' => [
                'eduPersonPrincipalName' => 'string',
            ],
            'ForceAuthn' => true,
            self::FLD_ATTRIBUTE_MAPPING => $this->{self::FLD_ATTRIBUTE_MAPPING},
            self::FLD_CUSTOM_HOOKS => $this->{self::FLD_CUSTOM_HOOKS},
        ];

        if (!empty($this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION})) {
            $result['AssertionConsumerService']['Location'] = $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION};
        }
        if (!empty($this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING})) {
            $result['AssertionConsumerService']['Binding'] = $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING};
        }

        if (!empty($this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION})) {
            $result['SingleLogoutService']['Location'] = $this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION};
        }
        if (!empty($this->{self::FLD_SINGLE_LOGOUT_SERVICE_BINDING})) {
            $result['SingleLogoutService']['Binding'] = $this->{self::FLD_SINGLE_LOGOUT_SERVICE_BINDING};
        }

        return $result;
    }

    public function beforeCreateUpdateHook(): void
    {
        $this->fetchRemoteMetaData();
    }

    public function fetchRemoteMetaData(): void
    {
        $dtd = <<<DTD
<!ELEMENT el (#PCDATA)>
DTD;

        libxml_set_external_entity_loader(function() use($dtd) {
                $f = fopen("php://temp", "r+");
                fwrite($f, $dtd);
                rewind($f);
                return $f;
            }
        );

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$this->{self::FLD_METADATA_URL} || stripos($this->{self::FLD_METADATA_URL}, 'http') !== 0 ||
                ! $dom->load($this->{self::FLD_METADATA_URL})) {
            return;
        }

        $rootNode = $dom->firstChild;
        if ($rootNode->hasAttributes() && ($attr = $rootNode->attributes->getNamedItem('entityID'))) {
            $this->{self::FLD_ENTITYID} = $attr->nodeValue;
        }

        foreach ($rootNode->childNodes as $node) {
            if ('SPSSODescriptor' === $node->nodeName) {
                $foundPostLocation = false;
                $foundRedirectLocation = false;
                foreach ($node->childNodes as $childNode) {
                    switch($childNode->nodeName) {
                        case 'SingleLogoutService':
                            if ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === SSO_Config::SAML2_BINDINGS_REDIRECT &&
                                    ($attr = $childNode->attributes->getNamedItem('Location'))) {
                                $foundRedirectLocation = true;
                                $this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION} = $attr->nodeValue;
                                $this->{self::FLD_SINGLE_LOGOUT_SERVICE_BINDING} = SSO_Config::SAML2_BINDINGS_REDIRECT;
                            } elseif ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === SSO_Config::SAML2_BINDINGS_POST && !$foundRedirectLocation
                                    && ($attr = $childNode->attributes->getNamedItem('Location'))) {
                                $this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION} = $attr->nodeValue;
                                $this->{self::FLD_SINGLE_LOGOUT_SERVICE_BINDING} = SSO_Config::SAML2_BINDINGS_POST;
                        }
                            break;
                        case 'AssertionConsumerService':
                            if ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === SSO_Config::SAML2_BINDINGS_REDIRECT &&
                                    ($attr = $childNode->attributes->getNamedItem('Location')) && !$foundPostLocation) {
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION} = $attr->nodeValue;
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING} = SSO_Config::SAML2_BINDINGS_REDIRECT;
                            } elseif ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === SSO_Config::SAML2_BINDINGS_POST &&
                                    ($attr = $childNode->attributes->getNamedItem('Location'))) {
                                $foundPostLocation = true;
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING} = SSO_Config::SAML2_BINDINGS_POST;
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION} = $attr->nodeValue;
                            }
                            break;
                    }
                }
            }
        }
    }

}
