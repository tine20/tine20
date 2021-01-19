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
 * AuthGenericSmsMFAUserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_PinUserConfig extends Tinebase_Record_NewAbstract
    implements Tinebase_Auth_MFA_UserConfigInterface
{
    public const MODEL_NAME_PART = 'MFA_PinUserConfig';

    public const FLD_PIN = 'pin';
    public const FLD_HASHED_PIN = 'hashed_pin';

    protected $_hashedPin;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'PIN',
        self::TITLE_PROPERTY                => 'Static PIN for user: {% if pin %}{{ pin }}{% else %}*****{% endif %}', //_('Static PIN for user: {% if pin %}{{ pin }}{% else %}*****{% endif %}')
        
        self::FIELDS                        => [
            self::FLD_PIN                       => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'New PIN', // _('New PIN')
                self::REF_MODEL_FIELD               => self::FLD_HASHED_PIN,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Tinebase_Auth_MFA_PinValidator::class,
                ],
                self::CONVERTERS                    => [
                    Tinebase_Model_Converter_HashPassword::class,
                ]
            ],
            self::FLD_HASHED_PIN                => [
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
        if (isset($_data[self::FLD_HASHED_PIN])) {
            $this->_hashedPin = $_data[self::FLD_HASHED_PIN];
            unset($_data[self::FLD_HASHED_PIN]);
        }
        parent::setFromArray($_data);
    }

    public function validate($data): bool
    {
        return Hash_Password::validate($this->_hashedPin, $data);
    }

    public function getHashedPin()
    {
        return $this->_hashedPin;
    }

    public function toFEArray(): array
    {
        return $this->toArray();
    }

    public function getMessages()
    {
        // TODO: Implement getMessages() method.
    }
}
