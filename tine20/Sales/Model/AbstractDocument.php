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
class Sales_Model_AbstractDocument extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AbstractDocument';

    const FLD_ID = 'id';
    const FLD_CONFIG = 'config';
    const FLD_CONFIG_CLASS = 'config_class';
    const FLD_DOCUMENT_NUMBER = 'document_number'; // kommt aus incrementable, in config einstellen welches incrementable fuer dieses model da ist!
    const FLD_REFERENCE_DOCUMENT = 'reference_document'; // virtual, link
    const FLD_NOTE = 'note';
    const FLD_RECIPIENT_CLASS = 'recipient_class'; // Kunde od. Organisation
    const FLD_RECIPIENT_ID = 'recipient_id';
    const FLD_RECIPIENT_REFERENCE = 'recipient_reference'; // varchar 255
    const FLD_CONTACT_CLASS = 'contact_class'; // Anprechpartner
    const FLD_CONTACT_ID = 'contact_id';
    const FLD_DOCUMENT_DATE = 'date'; // Belegdatum NICHT Buchungsdatum, das kommt noch unten
    const FLD_PAYMENT_TERMS_ID = 'payment_terms_id'; // Sales_Model_PaymentTerms
    
    const FLD_POSITIONS = 'positions'; // virtuell recordSet
    
    const FLD_NET_SUM = 'net_sum';
    
    const FLD_INVOICE_DISCOUNT_TYPE = 'invoice_discount_type'; // PERCENTAGE|SUM
    const FLD_INVOICE_DISCOUNT_PERCENTAGE = 'invoice_discount_percentage'; // automatische Berechnung je nach tupe
    const FLD_INVOICE_DISCOUNT_SUM = 'invoice_discount_sum'; // automatische Berechnung je nach type
    
    const FLD_SALES_TAX = 'sales_tax';
    const FLD_GROSS_SUM = 'gross_sum';
    
    const FLD_HEADER_TEXT = 'cost_bearer_id';
    const FLD_FOOTER_TEXT = 'cost_bearer_id';
    
    const FLD_BOOKING_DATE = 'booking_date'; // ggf. nur bei Rechnung ud. Storno
    
    // jedes dokument bekommt noch:
    // <dokumentenart>_TYPE z.B Rechnungsart (Beitragsrechnung, Service, ...) keyfield mit metadaten wie z.B. template, textbausteine etc.
    //   vermutlich nicht änderbar?
    // <dokumentenart>_STATUS z.B. Rechnungsstatus (Ungebucht, Gebucht, Verschickt, Bezahlt)
    //   übergänge haben regeln (siehe SAAS mechanik)
    
    // INVOICE: 
    //  - IS_REVERSED bool // storno
    //  - INVOICE_REPORTING enum (AUTO|MANU) // Rechnungslegung
    //  - DOCUMENT_TYPE (jetziger TYPE) // Belegart (Rechnung/Storno/Gutschrift)
    //  - INVOICE_TYPE (Beitragsrechnung, ...) // Rechnungsart
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Document', // ngettext('Document', 'Documents', n)
        self::RECORDS_NAME                  => 'Documents',
        self::TITLE_PROPERTY                => self::FLD_CONFIG,

        self::FIELDS                        => [
            self::FLD_ID                        => [
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DOCUMENT_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING, // @TODO nummerkreise sollen zentral confbar sein!!!
                self::LABEL                     => 'Document Number', //_('Document Number')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_REFERENCE_DOCUMENT => [
                self::TYPE                      => self::TYPE_VIRTUAL, // @TODO nummerkreise sollen zentral confbar sein!!!
                self::LABEL                     => 'Reference Document', //_('Document Number')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG => [
                    self::TYPE => self::TYPE_RELATION
                ],

            ],
            
            self::FLD_REFERENCE_DOCUMENT => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LABEL                     => 'MFA Device Type', //_('MFA Device Type')
                self::CONFIG                    => [
                    // not used in client, @see \Admin_Frontend_Json::getPossibleMFAs
                    // needs to implement Tinebase_Auth_MFA_UserConfigInterface
                    self::AVAILABLE_MODELS              => [
                        Tinebase_Model_MFA_SmsUserConfig::class,
                        Tinebase_Model_MFA_PinUserConfig::class,
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
}
