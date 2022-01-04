<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * abstract Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
abstract class Sales_Model_Document_Abstract extends Tinebase_Record_NewAbstract
{
    //const MODEL_NAME_PART = ''; // als konkrete document_types gibt es Offer, Order, DeliveryNote, Invoice (keine Gutschrift!)

    public const FLD_ID = 'id';
    public const FLD_DOCUMENT_NUMBER = 'document_number'; // kommt aus incrementable, in config einstellen welches incrementable fuer dieses model da ist!
    public const FLD_DOCUMENT_LANGUAGE = 'document_language';
    public const FLD_DOCUMENT_CATEGORY = 'document_category'; // keyfield - per default "standard". brauchen wir z.B. zum filtern, zur Auswahl von Textbausteinen, Templates etc.

    public const FLD_PRECURSOR_DOCUMENTS = 'precursor_documents'; // virtual, link
    public const FLD_BOILERPLATES = 'boilerplates';

    public const FLD_CUSTOMER_ID = 'customer_id'; // Kunde(Sales) (Optional beim Angebot, danach required). denormalisiert pro beleg, denormalierungs inclusive addressen, exklusive contacts
    public const FLD_CONTACT_ID = 'contact_id'; // Kontakt(Addressbuch) per default AP Extern, will NOT be denormalized
    // TODO FIXME denormalized.... as json in the document or as copy in the db?
    public const FLD_RECIPIENT_ID = 'recipient_id'; // Adresse(Sales) -> bekommt noch ein. z.Hd. Feld(text). denormalisiert pro beleg. muss nicht notwendigerweise zu einem kunden gehören. kann man aus kontakt übernehmen werden(z.B. bei Angeboten ohne Kunden)

    public const FLD_DOCUMENT_TITLE = 'document_title';
    public const FLD_DOCUMENT_DATE = 'date'; // Belegdatum,  defaults empty, today when booked and not set differently
    public const FLD_CUSTOMER_REFERENCE = 'customer_reference'; // varchar 255

    public const FLD_POSITIONS = 'positions'; // virtuell recordSet
    public const FLD_POSITIONS_NET_SUM = 'positions_net_sum';
    public const FLD_POSITIONS_DISCOUNT_SUM = 'positions_discount_sum';

    public const FLD_INVOICE_DISCOUNT_TYPE = 'invoice_discount_type'; // PERCENTAGE|SUM
    public const FLD_INVOICE_DISCOUNT_PERCENTAGE = 'invoice_discount_percentage'; // automatische Berechnung je nach tupe
    public const FLD_INVOICE_DISCOUNT_SUM = 'invoice_discount_sum'; // automatische Berechnung je nach type

    public const FLD_NET_SUM = 'net_sum';
    public const FLD_SALES_TAX = 'sales_tax';
    public const FLD_SALES_TAX_BY_RATE = 'sales_tax_by_rate';
    public const FLD_GROSS_SUM = 'gross_sum';

    public const FLD_PAYMENT_METHOD = 'payment_method'; // Sales_Model_PaymentMethod KeyField

    public const FLD_COST_CENTER_ID = 'cost_center_id';
    public const FLD_COST_BEARER_ID = 'cost_bearer_id'; // ist auch ein cost center
    public const FLD_DESCRIPTION = 'description';

    // <dokumentenart>_STATUS z.B. Rechnungsstatus (Ungebucht, Gebucht, Verschickt, Bezahlt)
    //   übergänge haben regeln (siehe SAAS mechanik)

    // ORDER:
    //  - INVOICE_RECIPIENT_ID // abweichende Rechnungsadresse, RECIPIENT_ID wenn leer
    //  - INVOICE_CONTACT_ID // abweichender Rechnungskontakt, CONTACT_ID wenn leer
    //  - INVOICE_STATUS // // keyfield: offen, gebucht; berechnet sich automatisch aus den zug. Rechnungen
    //  - DELIVERY_RECIPIENT_ID // abweichende Lieferadresse, RECIPIENT_ID wenn leer
    //  - DELIVERY_CONTACT_ID // abweichender Lieferkontakt, CONTACT_ID wenn leer
    //  - DELIVERY_STATUS // keyfield: offen, geliefert; brechnet sich automatisch aus den zug. Lieferungen
    //  pro position:
    //    - 1:n lieferpositionen (verknüpfung zu LS positionen)
    //    - zu liefern (automatisch auf anzahl, kann aber geändert werden um anzahl für erzeugten LS zu bestimmen)
    //    - geliefert (berechnet sich automatisch)
    //    - 1:n rechnungspositionen (verknüpfung zu RG positionen)
    //    - zu berechnen (s.o.)
    //    - berechnet (s.o.)
    //  - ORDER_STATUS // keyfield: eingegangen (order änderbar, nicht erledigt), angenommen (nicht mehr änderbar (AB ist raus), nicht erledigt), abgeschlossen(nicht mehr änderbar, erledigt) -> feld berechnet sich automatisch! (ggf. lassen wir das abschließen doch zu aber mit confirm)

    // DELIVERY_NOTE
    // - DELIVERY_STATUS // keyfield erstellt(Ungebucht, offen), geliefert(gebucht, abgeschlossen)
    //    NOTE: man könnte einen ungebuchten Status als Packliste einführen z.B. Packliste(ungebucht, offen)

    // INVOICE:
    //  - IS_REVERSED bool // storno
    //  - INVOICE_REPORTING enum (AUTO|MANU) // Rechnungslegung
    //  - INVOICE_TYPE (jetziger TYPE) // Rechnungsart (Rechnung/Storno)
    //  - INVOICE_STATUS: keyfield: proforma(Ungebucht, offen), gebucht(gebucht, offen),  Verschickt(gebucht, offen), Bezahlt(gebucht, geschlossen)
    //  obacht: bei storno rechnung wird der betrag (pro zeile) im vorzeichen umgekehrt

    // Achtung: Hinter den status keyfields verbergen sich jeweils noch fachliche workflows die jeweils noch (dazu) konfiguiert werden können
    //  -> staus übergänge definieren
    //  -> Ungebucht/Gebucht merkmal (Gebucht ist nicht mehr änderbar) (siehe CRM status merkmale)
    //  -> Offen/Abgeschlossen  merkmal (Abgeschlossen fällt aus der standar-filterung) (siehe CRM staus merkmale)
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::RECORD_NAME                   => 'Document', // ngettext('Document', 'Documents', n)
        self::RECORDS_NAME                  => 'Documents', // gettext('GENDER_Document')
        self::TITLE_PROPERTY                => self::FLD_DOCUMENT_NUMBER,
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,
        self::EXPOSE_HTTP_API               => true,

        self::HAS_ATTACHMENTS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => false,
        self::HAS_RELATIONS => true,
        self::HAS_TAGS => true,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CUSTOMER_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'delivery'      => [],
                        'billing'       => [],
                        'postal'        => [],
                        'cpextern_id'   => [],
                        'cpintern_id'   => [],
                    ],
                ],
                self::FLD_RECIPIENT_ID => [],
            ]
        ],

        self::FIELDS                        => [
            self::FLD_DOCUMENT_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING, // @TODO nummerkreise sollen zentral confbar sein!!!
                self::LABEL                     => 'Document Number', //_('Document Number')
                self::CONFIG                    => [

                ],
                /*self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ]*/
            ],
            self::FLD_DOCUMENT_LANGUAGE => [
                self::LABEL                 => 'Language', // _('Language')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Sales_Config::LANGUAGES_AVAILABLE,
            ],
            self::FLD_DOCUMENT_CATEGORY => [
                self::LABEL                 => 'Category', // _('Category')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Sales_Config::DOCUMENT_CATEGORY,
            ],

            self::FLD_PRECURSOR_DOCUMENTS => [
                // needs to be set by concret implementation
                self::TYPE => self::TYPE_RECORDS,
                self::CONFIG => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    //self::MODEL_NAME            => Sales_Model_SubProductMapping::MODEL_NAME_PART,
                    // ? self::REF_ID_FIELD          => Sales_Model_SubProductMapping::FLD_PARENT_ID,
                ],
            ],
            self::FLD_BOILERPLATES      => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::DISABLED              => true,
//                self::LABEL                 => 'Boilerplates', // _('Boilerplates')
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Boilerplate::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Boilerplate::FLD_DOCUMENT_ID,
                ],
//                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => []],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    //Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],

            self::FLD_DOCUMENT_TITLE => [
                self::LABEL                         => 'Title', // _('Title')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_DOCUMENT_DATE             => [
                self::LABEL                         => 'Document Date', //_('Document Date')
                self::TYPE                          => self::TYPE_DATE,
                self::NULLABLE                      => true,
            ],
            self::FLD_CUSTOMER_REFERENCE        => [
                self::LABEL                         => 'Customer Reference', //_('Customer Reference')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],

            self::FLD_CUSTOMER_ID       => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Customer', // _('Customer')
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Customer::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Customer::FLD_DOCUMENT_ID,
                ],
                self::VALIDATORS            => [ // only for offers this is allow empty true, by default its false
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_RECIPIENT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Recipient', //_('Recipient')
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Address::FLD_DOCUMENT_ID,
                    self::TYPE                  => Sales_Model_Document_Address::TYPE_BILLING
                ],
            ],
            self::FLD_CONTACT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Reference Person', //_('Reference Person')
                // TODO add resolve deleted flag? guess that would be nice
                self::CONFIG                => [
                    self::APP_NAME              => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_PART_NAME,
                ],
                self::NULLABLE              => true,
            ],

            self::FLD_POSITIONS                 => [
                // needs to be set by concret implementation
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::REF_ID_FIELD                  => Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID,
                    self::DEPENDENT_RECORDS             => true,
                    self::PAGING                        => ['sort' => Sales_Model_DocumentPosition_Abstract::FLD_SORTING],
                ],
            ],
            self::FLD_POSITIONS_NET_SUM                   => [
                self::LABEL                         => 'Positions Net Sum', //_('Positions Net Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_POSITIONS_DISCOUNT_SUM   => [
                self::LABEL                         => 'Positions Discount Sum', //_('Positions Discount Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],

            self::FLD_INVOICE_DISCOUNT_TYPE     => [
                self::LABEL                         => 'Invoice Discount Type', //_('Invoice Discount Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::INVOICE_DISCOUNT_TYPE,
                self::NULLABLE                      => true,
                self::DEFAULT_VAL                   => 'SUM'
            ],
            self::FLD_INVOICE_DISCOUNT_PERCENTAGE => [
                self::LABEL                         => 'Invoice Discount Percentage', //_('Invoice Discount Percentage')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::NULLABLE                      => true,
            ],
            self::FLD_INVOICE_DISCOUNT_SUM      => [
                self::LABEL                         => 'Invoice Discount Sum', //_('Invoice Discount Sum')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DISCOUNT,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    'price_field'   => self::FLD_POSITIONS_NET_SUM,
                    'net_field'     => self::FLD_NET_SUM
                ],
            ],

            self::FLD_NET_SUM                   => [
                self::LABEL                         => 'Net Sum', //_('Net Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SALES_TAX                 => [
                self::LABEL                         => 'Sales Tax', //_('Sales Tax')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SALES_TAX_BY_RATE         => [
                self::LABEL                         => 'Sales Tax by Rate', //_('Sales Tax by Rate')
                self::TYPE                          => self::TYPE_JSON,
                self::NULLABLE                      => true,
                self::DISABLED                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_GROSS_SUM                 => [
                self::LABEL                         => 'Gross Sum', //_('Gross Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],

            self::FLD_PAYMENT_METHOD            => [
                self::LABEL                         => 'Payment Method', //_('Payment Method')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::PAYMENT_METHODS,
                self::NULLABLE                      => true,
            ],

            self::FLD_COST_CENTER_ID            => [
                self::LABEL                         => 'Cost Center', //_('Cost Center')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_CostCenter::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_COST_BEARER_ID            => [
                self::LABEL                         => 'Cost Bearer', //_('Cost Bearer')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_CostCenter::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_DESCRIPTION                      => [
                self::LABEL                         => 'Internal Note', //_('Internal Note')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
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
