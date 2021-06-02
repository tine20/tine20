<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for CommunityKey
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_CommunityIdentNr extends Tinebase_Record_NewAbstract
{
    const FLD_SATZ_ART = 'satzArt';
    const FLD_TEXTKENNZEICHEN = 'textkenzeichen';
    const FLD_ARS_LAND = 'arsLand';
    const FLD_ARS_RB = 'arsRB';
    const FLD_ARS_KREIS = 'arsKreis';
    const FLD_ARS_VB = 'arsVB';
    const FLD_ARS_GEM = 'arsGem';
    const FLD_ARS_COMBINED = 'arsCombined';
    const FLD_GEMEINDENAMEN = 'gemeindenamen';
    const FLD_FLAECHE = 'flaeche';
    const FLD_BEVOELKERUNG_GESAMT = 'bevoelkerungGesamt';
    const FLD_BEVOELKERUNG_MAENNLICH = 'bevoelkerungMaennlich';
    const FLD_BEVOELKERUNG_WEIBLICH = 'bevoelkerungWeiblich';
    const FLD_BEVOELKERUNG_JE_KM = 'bevoelkerungJeKm';
    const FLD_PLZ = 'plz';
    const FLD_LAENGENGRAD = 'laengengrad';
    const FLD_BREITENGRAD = 'breitengrad';
    const FLD_REISEGEBIET = 'reisegebiet';
    const FLD_GRAD_DER_VERSTAEDTERUNG = 'gradDerVerstaedterung';
    
    const MODEL_NAME_PART = 'CommunityIdentNr';
    const TABLE_NAME = 'cinumber';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => Tinebase_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Community Identification Number', 
        self::RECORDS_NAME => 'Community Identification Numbers', // ngettext('Community Identification Number', 'Community Identification Numbers', n)
        self::TITLE_PROPERTY => self::FLD_ARS_COMBINED,
        
        self::HAS_RELATIONS => false,
        self::HAS_ATTACHMENTS => true,
        self::HAS_NOTES => false,
        self::HAS_TAGS => true,
        
        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_ARS_COMBINED => [
                    self::COLUMNS => [self::FLD_ARS_COMBINED]
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_SATZ_ART => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Satzart', // _('Satzart')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_TEXTKENNZEICHEN => [
                self::TYPE => self::TYPE_INTEGER,
                self::SHY => true,
                self::LABEL => 'Textkennzeichen', // _('Textkennzeichen')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_LAND => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Land', // _('Land')
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_RB => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'RB', // _('RB')
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_KREIS => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Kreis', // _('Kreis')
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_VB => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'VB', // _('VB')
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_GEM => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Gem', // _('Gem')
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_COMBINED => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Amtlicher Regionalschlüssel', // _('Amtlicher Regionalschlüssel')
                self::NULLABLE => false,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                ],
            ],
            self::FLD_GEMEINDENAMEN => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                self::LABEL => 'Gemeindename', // _('Gemeindename')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_FLAECHE => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Fläche', // _('Fläche')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BEVOELKERUNG_GESAMT => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Bevölkerung insgesamt', // _('Bevölkerung insgesamt')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BEVOELKERUNG_MAENNLICH => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Bevölkerung männlich', // _('Bevölkerung männlich')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BEVOELKERUNG_WEIBLICH => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Bevölkerung weiblich', // _('Bevölkerung weiblich')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BEVOELKERUNG_JE_KM => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Bevölkerung je quadrat km', // _('Bevölkerung je quadrat km')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_PLZ => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'plz', // _('plz')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_LAENGENGRAD => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Längengrad', // _('Längengrad')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BREITENGRAD => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Breitengrad', // _('Breitengrad')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_REISEGEBIET => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 4,
                self::LABEL  => 'Reisegebiet', // _('Reisegebiet')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            self::FLD_GRAD_DER_VERSTAEDTERUNG => [
                self::NULLABLE => true,
                self::TYPE => self::TYPE_KEY_FIELD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Grad der Verstädterung', // _('Grad der Verstädterung')
                self::NAME                      => 'gradVerstaedterung',
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
