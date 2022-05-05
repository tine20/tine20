<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  MFA
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * MFA_UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  MFA
 */
class Tinebase_Model_MFA_UserConfig extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'MFA_UserConfig';

    const FLD_ID = 'id';
    const FLD_MFA_CONFIG_ID = 'mfa_config_id';
    const FLD_CONFIG = 'config';
    const FLD_CONFIG_CLASS = 'config_class';
    const FLD_NOTE = 'note';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Second factor config for user', // ngettext('Second factor config for user', 'Second factor configs for user', n)
        self::RECORDS_NAME                  => 'Second factor configs for user',
        self::TITLE_PROPERTY                => self::FLD_CONFIG,

        self::FIELDS                        => [
            self::FLD_ID                        => [
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_MFA_CONFIG_ID         => [
                self::TYPE                      => self::TYPE_STRING,
                self::DISABLED                  => TRUE,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CONFIG_CLASS           => [
                self::TYPE                      => self::TYPE_MODEL,
                self::LABEL                     => 'MFA Device Type', //_('MFA Device Type')
                self::CONFIG                    => [
                    // not used in client, @see \Admin_Frontend_Json::getPossibleMFAs
                    // needs to implement Tinebase_Auth_MFA_UserConfigInterface
                    self::AVAILABLE_MODELS              => [
                        Tinebase_Model_MFA_HOTPUserConfig::class,
                        Tinebase_Model_MFA_PinUserConfig::class,
                        Tinebase_Model_MFA_SmsUserConfig::class,
                        Tinebase_Model_MFA_TOTPUserConfig::class,
                        Tinebase_Model_MFA_WebAuthnUserConfig::class,
                        Tinebase_Model_MFA_YubicoOTPUserConfig::class,
                    ],
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CONFIG                    => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::LABEL                         => 'MFA Device Config', // _('MFA Device Config')
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_CONFIG_CLASS,
                    self::PERSISTENT                    => true,
                ],
                self::VALIDATORS            => [
                        Zend_Filter_Input::ALLOW_EMPTY => false,
                        Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_NOTE                      => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Note', //_('Note')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true,],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function toFEArray(): array
    {
        $result = $this->toArray();
        $result[self::FLD_CONFIG] = $this->{self::FLD_CONFIG}->toFEArray();

        return $result;
    }

    public function updateUserOldRecordCallback(Tinebase_Model_FullUser $newUser, Tinebase_Model_FullUser $oldUser)
    {
        if (method_exists($this->{self::FLD_CONFIG}, __FUNCTION__)) {
            $this->{self::FLD_CONFIG}->updateUserOldRecordCallback($newUser, $oldUser, $this);
        }
    }

    public function updateUserNewRecordCallback(Tinebase_Model_FullUser $newUser, ?Tinebase_Model_FullUser $oldUser)
    {
        if (method_exists($this->{self::FLD_CONFIG}, __FUNCTION__)) {
            $this->{self::FLD_CONFIG}->updateUserNewRecordCallback($newUser, $oldUser, $this);
        }
    }

    /**
     * validate and filter the the internal data
     *
     * @param bool $_throwExceptionOnInvalidData
     * @return bool
     * @throws Tinebase_Exception_Record_Validation
     */
    public function isValid($_throwExceptionOnInvalidData = false)
    {
        return parent::isValid($_throwExceptionOnInvalidData) &&
            (! $this->{self::FLD_CONFIG} instanceof Tinebase_Record_Interface || $this->{self::FLD_CONFIG}->isValid($_throwExceptionOnInvalidData));
    }
}
