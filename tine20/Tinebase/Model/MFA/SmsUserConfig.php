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
class Tinebase_Model_MFA_SmsUserConfig extends Tinebase_Record_NewAbstract
    implements Tinebase_Auth_MFA_UserConfigInterface
{
    const MODEL_NAME_PART = 'MFA_SmsUserConfig';

    const FLD_CELLPHONENUMBER = 'cellphonenumber';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'SMS',
        self::TITLE_PROPERTY                => 'Auth codes are send to: {{ cellphonenumber }}', //_('Auth codes are send to: {{ cellphonenumber }}')

        self::FIELDS                        => [
            self::FLD_CELLPHONENUMBER           => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                     => 'Cell Phone Number' // _('Cell Phone Number')
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
        return $this->toArray();
    }
}
