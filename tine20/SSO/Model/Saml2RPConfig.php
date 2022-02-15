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
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::LABEL                 => 'Consumer Service Binding', // _('Consumer Service Binding')
            ],
            self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION   => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::LABEL                 => 'Consumer Service Location', // _('Consumer Service Location')
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
        return [
            'AssertionConsumerService' => [
                'Location' => $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION},
                'Binding' => $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING},
            ],
            'SingleLogoutService' => [
                'Location' => $this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION},
            ],
            'IDPList' => [ /* add us, aka IDP */],
            'entityid' => $this->{self::FLD_ENTITYID},
            'attributeencodings' => [
                'eduPersonPrincipalName' => 'string',
            ],
            'ForceAuthn' => true,
            self::FLD_ATTRIBUTE_MAPPING => $this->{self::FLD_ATTRIBUTE_MAPPING},
            self::FLD_CUSTOM_HOOKS => $this->{self::FLD_CUSTOM_HOOKS},
        ];
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
                foreach ($node->childNodes as $childNode) {
                    switch($childNode->nodeName) {
                        case 'SingleLogoutService':
                            if ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' &&
                                    ($attr = $childNode->attributes->getNamedItem('Location'))) {
                                $this->{self::FLD_SINGLE_LOGOUT_SERVICE_LOCATION} = $attr->nodeValue;
                            }
                            break;
                        case 'AssertionConsumerService':
                            if ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' &&
                                    ($attr = $childNode->attributes->getNamedItem('Location')) && !$foundPostLocation) {
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION} = $attr->nodeValue;
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING} = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
                            } elseif ($childNode->hasAttributes() && ($attr = $childNode->attributes->getNamedItem('Binding'))
                                    && $attr->nodeValue === 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' &&
                                    ($attr = $childNode->attributes->getNamedItem('Location'))) {
                                $foundPostLocation = true;
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_BINDING} = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';
                                $this->{self::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION} = $attr->nodeValue;
                            }
                            break;
                    }
                }
            }
        }
    }

}
