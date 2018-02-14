<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * AreaLockConfig Model
 * @see \Tinebase_Config::AREA_LOCKS
 *
 * @package     Tinebase
 * @subpackage  Adapter
 *
 * @property provider
 * @property provider_config
 * @property area
 * @property validity
 */
class Tinebase_Model_AreaLockConfig extends Tinebase_Record_Abstract
{
    /**
     * supported validity
     */
    const VALIDITY_ONCE = 'once';
    const VALIDITY_SESSION = 'session';
    const VALIDITY_LIFETIME = 'lifetime';
    const VALIDITY_PRESENCE = 'presence';
    const VALIDITY_DEFINEDBYPROVIDER = 'definedbyprovider';

    /**
     * some predefined areas
     */
    const AREA_LOGIN = 'Tinebase.login';
    const AREA_DATASAFE = 'Tinebase.datasafe';

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
        'recordName'        => 'Area Lock Configuration',
        'recordsName'       => 'Area Lock Configurations', // ngettext('Area Lock Configuration', 'Area Lock Configurations', n)
        'titleProperty'     => 'area',

        'modlogActive'      => false, // @todo activate?

        'appName'           => 'Tinebase',
        'modelName'         => 'AreaLockConfig',

        'fields'            => [
            'area' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Area', // _('Area')
                'queryFilter'   => true
            ],
            'provider' => [
                // NOTE: must implement Tinebase_Auth_Interface
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Provider', // _('Provider')
                'queryFilter'   => true,
            ],
             /** example config:
              *
              * array(
              *      'url'                   => 'https://localhost/validate/check',
              *      'allow_self_signed'     => true,
              *      'ignorePeerName'        => true,
              * )
              *
              * NOTE: as this might contain confidential data it is removed (in toArray) before sent to any client via
              *       getRegistryData()
              */
            'provider_config' => [
                'type'          => 'array',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Provider Configuration', // _('Provider Configuration')
            ],
            'validity' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    'presence' => 'required',
                    ['InArray', [
                        self::VALIDITY_ONCE, // @todo define ONCE (not implemented yet)
                        self::VALIDITY_SESSION, // valid until session ends
                        self::VALIDITY_LIFETIME, // @see lifetime
                        self::VALIDITY_PRESENCE, // lifetime is relative to last presence recording (requires presence api)
                        self::VALIDITY_DEFINEDBYPROVIDER, // provider can define own rules (not implemented yet)
                    ]],
                ],
                'label'         => 'Validity', // _('Validity')
                'queryFilter'   => true,
            ],
            // absolute lifetime from unlock
            'lifetime' => [
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Lifetime in Seconds', // _('Lifetime in Seconds')
            ],
            // @todo add more fields:
            // individual: true,      // each area must be unlocked individually (when applied hierarchically / with same provider) -> NOT YET
            // public_options: // provider specific _public_ options
            // allowEmpty?
        ]
    ];

    /**
     * returns array with record related properties
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);

        // unset provider_config as this is confidential
        unset($result['provider_config']);

        return $result;
    }
}
