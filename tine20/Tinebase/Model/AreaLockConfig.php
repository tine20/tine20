<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * AreaLockConfig Model
 * @see \Tinebase_Config::AREA_LOCKS
 *
 * @package     Tinebase
 * @subpackage  Adapter
 *
 * @property Tinebase_Record_RecordSet provider_configs
 * @property array areas
 * @property array mfas
 * @property string validity
 * @property int lifetime
 */
class Tinebase_Model_AreaLockConfig extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'AreaLockConfig';

    public const FLD_AREAS = 'areas';
    public const FLD_AREA_NAME = 'area_name';
    public const FLD_LIFETIME = 'lifetime';
    public const FLD_MFAS = 'mfas';
    public const FLD_POLICY = 'policy';
    public const FLD_VALIDITY = 'validity';

    public const POLICY_REQUIRED = 'required';
    public const POLICY_ENCOURAGED = 'encouraged';
    /**
     * supported validity
     */
    const VALIDITY_ONCE = 'once';
    const VALIDITY_SESSION = 'session';
    const VALIDITY_LIFETIME = 'lifetime';
    const VALIDITY_PRESENCE = 'presence';

    /**
     * some predefined areas
     */
    const AREA_LOGIN = 'Tinebase_login';
    const AREA_DATASAFE = 'Tinebase_datasafe';

    /**
     * supported providers
     */
    const PROVIDER_PIN = 'pin';
    const PROVIDER_USERPASSWORD = 'userpassword';
    const PROVIDER_TOKEN = 'token';

    /** @var string|null */
    protected $_key;

    /** @var Tinebase_AreaLock_Interface|null  */
    protected $_backend;


    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::RECORD_NAME           => 'Area Lock Configuration',
        self::RECORDS_NAME          => 'Area Lock Configurations', // ngettext('Area Lock Configuration', 'Area Lock Configurations', n)
        self::TITLE_PROPERTY        => 'area',

        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::FIELDS                => [
            self::FLD_AREA_NAME         => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Area name' // _('Area name')
            ],
            /**
             * array of area names, like ["Tinebase_login","Addressbook","Calendar.saveEvent"] etc.
             */
            self::FLD_AREAS             => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Areas' // _('Areas')
            ],
             /** example config:
              *
              * [[
              *      // some auth adapter below Tinebase_Auth (i.e. Tinebase_Auth_PrivacyIdea)
              *      // NOTE: must implement Tinebase_Auth_Interface
              *      'adapter'               => 'PrivacyIdea',
              *      'url'                   => 'https://localhost/validate/check',
              *      'allow_self_signed'     => true,
              *      'ignorePeerName'        => true,
              * ],[
              *     // alternative provider etc.
              * ]]
              *
              * NOTE: as this might contain confidential data it is removed (in toArray) before sent to any client via
              *       getRegistryData()
              */
            /**
             * array of Tinebase_Model_MFA_Config::id's
             */
            self::FLD_MFAS  => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                /*
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG                => [
                    self::STORAGE               => self::TYPE_JSON,
                    self::APP_NAME              => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME            => Tinebase_Model_MFA_Config::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],*/
                self::LABEL                 => 'MFAs', // _('MFAs')
            ],
            self::FLD_VALIDITY => [
                'type'          => 'string',
                'validators'    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    'presence' => 'required',
                    ['InArray', [
                        self::VALIDITY_ONCE, // default
                        self::VALIDITY_SESSION, // valid until session ends
                        self::VALIDITY_LIFETIME, // @see lifetime
                        self::VALIDITY_PRESENCE, // lifetime is relative to last presence recording (requires presence api)
                    ]],
                ],
                'label'         => 'Validity', // _('Validity')
                'default'       => self::VALIDITY_ONCE
            ],
            // absolute lifetime from unlock
            self::FLD_LIFETIME => [
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Lifetime in Minutes', // _('Lifetime in Minutes')
            ],
            self::FLD_POLICY => [
                self::LABEL         => 'Policy', // _('Policy')
                self::TYPE          => self::TYPE_STRING,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => self::POLICY_ENCOURAGED,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [self::POLICY_ENCOURAGED, self::POLICY_REQUIRED]],
                ],
                self::DEFAULT_VAL   => self::POLICY_ENCOURAGED,
            ],
        ]
    ];

    /** be aware of changes to the data! key will not change once calculated! */
    public function getKey(): string
    {
        if (null === $this->_key) {
            $this->_key = md5(json_encode($this->{self::FLD_AREAS}));
        }
        return $this->_key;
    }

    public function areaMatch(string $area): bool
    {
        $areas = explode('.', $area);
        foreach ($this->_properties[self::FLD_AREAS] as $testArea) {
            $testAreas = explode('.', $testArea);
            foreach ($areas as $key => $val) {
                if (!isset($testAreas[$key])) {
                    return true;
                }
                if ($testAreas[$key] !== $val) {
                    break;
                }
                unset($testAreas[$key]);
            }
            if (empty($testAreas)) {
                return true;
            }
        }
        return false;
    }

    public function getUserMFAIntersection(Tinebase_Model_FullUser $user): Tinebase_Record_RecordSet
    {
        if (!$user->mfa_configs) {
            return new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
        }
        return $user->mfa_configs->filter(function($val) {
            return in_array($val->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID}, $this->{self::FLD_MFAS});
        });
    }

    public function getBackend(): Tinebase_AreaLock_Interface
    {
        if (null === $this->_backend) {
            switch (strtolower($this->{Tinebase_Model_AreaLockConfig::FLD_VALIDITY})) {
                case Tinebase_Model_AreaLockConfig::VALIDITY_SESSION:
                case Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME:
                    $this->_backend = new Tinebase_AreaLock_Session($this);
                    break;
                case Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE:
                    $this->_backend = new Tinebase_AreaLock_Presence($this);
                    break;
                // case Tinebase_Model_AreaLockConfig::VALIDITY_DEFINEDBYPROVIDER:
                // case Tinebase_Model_AreaLockConfig::VALIDITY_ONCE:
                // @todo add support
                default:
                    throw new Tinebase_Exception_InvalidArgument('validity ' .
                        $this->{Tinebase_Model_AreaLockConfig::FLD_VALIDITY} . ' not supported yet');
            }
        }
        return $this->_backend;
    }
}
