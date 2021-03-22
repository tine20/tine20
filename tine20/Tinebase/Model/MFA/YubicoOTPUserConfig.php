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
 * Yubico OTP MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_YubicoOTPUserConfig extends Tinebase_Auth_MFA_AbstractUserConfig
{
    public const MODEL_NAME_PART = 'MFA_YubicoOTPUserConfig';

    public const FLD_AES_KEY = 'aes_key';
    public const FLD_CC_ID = 'cc_id';
    public const FLD_PRIVAT_ID = 'private_id';
    public const FLD_PUBLIC_ID = 'public_id';
    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_COUNTER = 'counter';
    public const FLD_SESSIONC = 'sessionc';

    protected $_aesKey;
    protected $_privatId;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Yubico OTP',
        self::TITLE_PROPERTY                => 'Yubico OTP',

        self::FIELDS                        => [
            self::FLD_PUBLIC_ID                 => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Yubico OTP public id', // _('Yubico OTP public id')
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_PRIVAT_ID                 => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Yubico OTP privat id', // _('Yubico OTP privat id')
            ],
            self::FLD_AES_KEY                   => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Yubico AES privat key', // _('Yubico AES privat key')
            ],
            self::FLD_CC_ID                     => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
            ],
            self::FLD_ACCOUNT_ID                => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
            ],
            self::FLD_COUNTER                   => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::DISABLED                      => true,
            ],
            self::FLD_SESSIONC                  => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::DISABLED                      => true,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function setFromArray(array &$_data)
    {
        if (isset($_data[self::FLD_AES_KEY])) {
            $this->_aesKey = $_data[self::FLD_AES_KEY];
            unset($_data[self::FLD_AES_KEY]);
        }
        if (isset($_data[self::FLD_PRIVAT_ID])) {
            $this->_privatId = $_data[self::FLD_PRIVAT_ID];
            unset($_data[self::FLD_PRIVAT_ID]);
        }
        parent::setFromArray($_data);
    }

    public function getAesKey(): ?string
    {
        return $this->_aesKey;
    }

    public function getPrivatId(): ?string
    {
        return $this->_privatId;
    }
}
