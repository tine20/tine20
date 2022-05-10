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

use ParagonIE\ConstantTime\Base32;


/**
 * TOTP MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_TOTPUserConfig extends Tinebase_Auth_MFA_AbstractUserConfig
{
    public const MODEL_NAME_PART = 'MFA_TOTPUserConfig';

    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_CC_ID = 'cc_id';
    public const FLD_SECRET = 'secret';

    protected $_secret;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Time based OTP (TOTP)', // gettext('GENDER_Time based OTP (TOTP)')
        self::RECORDS_NAME                  => 'Time based OTPs (TOTP)', // ngettext('Time based OTP (TOTP)', 'Time based OTPs (TOTP)', n)
        self::TITLE_PROPERTY                => 'Time based OTP (TOPT) is configured', // _('Time based OTP (TOPT) is configured')

        self::FIELDS                        => [
            self::FLD_ACCOUNT_ID                => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
            ],
            self::ID                            => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
            ],
            self::FLD_SECRET                    => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Secret Key', // _('Secret Key')
            ],
            self::FLD_CC_ID                     => [
                self::TYPE                          => self::TYPE_STRING,
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
        if (isset($_data[self::FLD_SECRET])) {
            $this->_secret = $_data[self::FLD_SECRET];
            unset($_data[self::FLD_SECRET]);
        }
        parent::setFromArray($_data);
    }

    public function getSecret(): ?string
    {
        return $this->_secret;
    }

    public function updateUserOldRecordCallback(Tinebase_Model_FullUser $newUser, Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
        if (!$newUser->mfa_configs || !$newUser->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_ID,
                $userCfg->{Tinebase_Model_MFA_UserConfig::FLD_ID})) {
            $cc = Tinebase_Auth_CredentialCache::getInstance();
            $adapter = explode('_', get_class($cc->getCacheAdapter()));
            $adapter = end($adapter);
            try {
                $cc->setCacheAdapter('Shared');
                $cc->delete($this->{self::FLD_CC_ID});
            } finally {
                $cc->setCacheAdapter($adapter);
            }
        }
    }

    public function updateUserNewRecordCallback(Tinebase_Model_FullUser $newUser, ?Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
        $this->{self::FLD_ACCOUNT_ID} = $newUser->getId();
        if (($newSecret = $this->getSecret())) {
            if (preg_match('/[^A-Z2-7]/', $newSecret)) {
                throw new Tinebase_Exception_UnexpectedValue('secret needs to be base32 conform, consisting only of A-Z + 2-7 chars');
            }
            if (!$this->{self::ID}) {
                $this->{self::ID} = Tinebase_Record_Abstract::generateUID();
            }
            $cc = Tinebase_Auth_CredentialCache::getInstance();
            $adapter = explode('_', get_class($cc->getCacheAdapter()));
            $adapter = end($adapter);
            try {
                $cc->setCacheAdapter('Shared');
                $sharedCredentials = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($this->{self::ID},
                    $newSecret, null, true /* save in DB */, Tinebase_DateTime::now()->addYear(100));

                $this->{self::FLD_CC_ID} = $sharedCredentials->getId();

                if ($oldUser && $oldUser->mfa_configs && ($oldCfg = $oldUser->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_ID,
                        $userCfg->{Tinebase_Model_MFA_UserConfig::FLD_ID})) && ($ccId = $oldCfg
                        ->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{self::FLD_CC_ID}) && $ccId !== $this->{self::FLD_CC_ID}) {
                    $cc->delete($ccId);
                }
            } finally {
                $cc->setCacheAdapter($adapter);
            }
        }
    }
}
