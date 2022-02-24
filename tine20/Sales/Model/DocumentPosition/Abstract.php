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
class Sales_Model_DocumentPosition_Abstract extends Tinebase_Record_NewAbstract
{
    //const MODEL_NAME_PART = 'AbstractPosition';

    const FLD_DOCUMENT_ID = 'document_id';
    const FLD_PARENT_ID = 'parent_id';
    const FLD_TYPE = 'type';
    const FLD_SORTING = 'sorting'; // automatisch in 10000er schritten, shy
    const FLD_GROUPING = 'grouping'; // gruppierte darstellung, automatische laufende nummern pro gruppe(nicht persistiert)

    // guess this is not necessary const FLD_SUBPRODUCT_MAPPING = 'subproduct_mapping'; // "kreuztabelle" Sales_Model_SubproductMapping (nur für bundles nicht für set's?)

    const FLD_PRECURSOR_POSITION_MODEL = 'precursor_position_model'; // z.B. angebotsposition bei auftragsposition (virtual, link?)
    const FLD_PRECURSOR_POSITION = 'precursor_position'; // z.B. angebotsposition bei auftragsposition (virtual, link?)

    const FLD_POS_NUMBER = 'pos_number';
    const FLD_PRODUCT_ID = 'product_id';  // optional, es gibt auch textonlypositionen

    const FLD_TITLE = 'title'; // einzeiler/überschrift(fett) aus product übernommen sind änderbar
    const FLD_DESCRIPTION = 'description'; // aus product übernommen sind idr. änderbar
    const FLD_QUANTITY = 'quantity'; // Anzahl - aus produkt übernehmen, standard 1
    const FLD_USE_ACTUAL_QUANTITY  = 'use_actual_quantity'; // boolean, wenn true muss es eine verknüpfung mit n leistungsnachweisen (accountables) geben
    const FLD_UNIT = 'unit'; // Einheit - aus product übernehmen
    const FLD_UNIT_PRICE = 'unit_price'; // Einzelpreis - aus product übernehmen
    const FLD_POSITION_PRICE = 'position_price'; // Preis - anzahl * einzelpreis

    const FLD_POSITION_DISCOUNT_TYPE = 'position_discount_type'; // PERCENTAGE|SUM
    const FLD_POSITION_DISCOUNT_PERCENTAGE = 'position_discount_percentage'; // automatische Berechnung je nach tupe
    const FLD_POSITION_DISCOUNT_SUM = 'position_discount_sum'; // automatische Berechnung je nach type

    const FLD_NET_PRICE = 'net_price'; // Nettopreis - Preis - Discount

    const FLD_SALES_TAX_RATE = 'sales_tax_rate';
    const FLD_SALES_TAX = 'sales_tax'; // Mehrwertssteuer
    const FLD_GROSS_PRICE= 'gross_price'; // Bruttopreis - berechnen

    const FLD_COST_CENTER_ID = 'payment_cost_center_id'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat und ob user überschreiben kann
    const FLD_COST_BEARER_ID = 'payment_cost_bearer_id'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat, und ob user überschreiben kann

    //const FLD_XPROPS = 'xprops'; // z.B. entfaltungsart von Bundle od. Set merken



    // Produkte:
    // - shortcut intern (wird als kurzbez. in subproduktzuordnung übernommen, in pos nicht benötigt) - varchar 20
    // - title - varchar -> NAME! the field is called name ... fix the FE code to deal with it
    // - description - text
    // - gruppierung (Gruppierung von Positionen z.B. A implementierung, B regelm., C zusatz) - varchar - autocomplete
    // why? this is done once its added to a position no?

    // - anzahl
    // part of the position? not the product?

    // - anzahl aus accounting (ja/nein)
    // not sure, can we do when we do it?

    // - einheit - varchar
    // - verkaufspreis (pro einheit)
    // - steuersatz - percentage
    // - kostenstelle
    // - kostenträger
    // - ist verkaufsprodukt (bool) [nur verkaufsprodukte können in positionen direkt gewählt werden]
    // - Entfaltungsmodus (unfold type) (Nur aktiv wenn subprodukte ansonsten leer)
    //    Bundle -> Hauptprodukt wird übernommen und hat den Gesamtpreis, subprodukte werden nicht als belegpositionen übernommen (variablen beleg pos. werden trotzdem erzeugt!! + schattenpositionen die nicht angedruckt werden)
    //              die subproduktzuordnung wird übernommen!
    //    Set -> jedes subprodukt wird mit preis einzeln übernommen, hauptprodukt wird ohne preis übernommen
    //           gruppe aus hauptprodukt wird in jedes subprodukt übernommen
    //
    //    Zur Sicherheit: Bundles/Sets dürfen keine Bundles/Sets enthalten!
    // - subproduktzuordnung (eigene Tabelle)
    //   - shortcut z.B. vcpu, supportstunden, ... eindeutig pro hauptprodukt wird aus produkt übernommen (wird gebraucht für variablennamen im text des hauptproduktes)
    //   - hauptproduct_id (referenz für subprod tabelle)
    //   - product_id (referenz auf einzelprodukt)
    //   - inclusive_anzahl
    //   - variable_anzahl_position (frage nach variablen zusatzleistungen)
    //      - keine: inclusiveprodukt bekommt keine eigene belegposition (z.B. nur teil des positionstextes des Hauptproduktes, keine variable/zusatz anzahl in folge belegen (z.B. lieferung/rechnung) möglich)
    //      - gemeinsam: wenn mehrere hauptprodukte das referenzierte inclusive produkt beinhalten, entsteht im Beleg nur eine variable/zusatz position
    //      - eigene: wenn mehrere hauptprodukte das referenzierte inclusive produkt beinhalten, entsteht im Beleg je eine variable/zusatz position pro Hauptprodukt
    //     NOTE: in produkt ENUM, in position id zur position
    // - accountable (wie bisher)

    // in beschreibung des produktes. können variblen verwendet werden um auf die subprodukte zuzugreifen
    // {{ <shortcut>.<field> }} {{ <shortcut>.record.<productfield> }}
    // BSP: VM mit {{ cpu.inclusive }} vcpus und {{ ram.inclusive }} vram

    // Autrags belegposition die use_actual_quantity haben müssen verknüpfung zum konkreten leistungsnachweis (accountable)
    // im rahmen der leistungserfassung werden die tatsächlichen "Anzahl-Werte" ermittelt


    // Belegdruck:
    //  - im Hintergrund word export + pdf konvertierung
    //  - im UI nicht export btn sondern spezial btn mit eigener API (speichert, generiert syncron, liefert ganzen record zurück, ...)
    //    - vermutlich zwei buttons: drucken (proforma solang ungebucht, buchen und drucken)
    //  - pro documenttype ein template und eine export-definition (namenskonvention) (btn parametrisiert den export)
    //  - erst mal keine separaten templates pro kategorie, wenn wir das brauchen z.B. definition entsprechend benamen (namenskonvention)

    // @TODO: Vertäge - wie passt das rein? Klammer / auch im Standard? Muss es den geben?
    // @TODO: Preisstaffeln

    // @TODO: durchdenken zusatzfelder unten und velo sachen
    // zusatzfelder wegen 'dauerschuldverhältnissen'
    // laufzeit, start, ende etc.
    // abrechnungsperiode once, monthly, 3monthly, quarterly, yearly -> mehr gedanken machen
    // abrechnungszeitpunkt

    // @TODO: Schnittstelle zum accounting // Rechnungserzeugung (MW) -> passt grob!
    //  -> Sales.createAutoInvoices, getBillableContractIds (geht über Verträge)
    //    -> vertrags positionen (product_aggregate) "wissen" wann sie abgerechnet werden müssen (eigenschaft - rechnet ab)
    //     [produkt->accountable] "accountables" liste von (SalesModelAccountableAbstract)
    //          accountables können nach billables gefragt werden (z.B. speicherplatzpfad (accountable), speicherplatzaggregat (billable)
    //          accountables
    //

    // @TODO: MW Rechnbung reviewn -> migration zu neuer Rechnung hinschreiben!
    //  oder doch erst später? Erst mal KB ans laufen bringen?!

    const FLD_INTERNAL_NOTE = 'internal_note';

    const POS_TYPE_PRODUCT = 'PRODUCT';
    const POS_TYPE_HEADING = 'HEADING';
    const POS_TYPE_TEXT = 'TEXT';
    const POS_TYPE_ALTERNATIVE = 'ALTERNATIVE';
    const POS_TYPE_OPTIONAL = 'OPTIONAL';
    const POS_TYPE_PAGEBREAK = 'PAGEBREAK';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        //self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Position', // ngettext('Position', 'Positions', n)
        self::RECORDS_NAME                  => 'Positions', // gettext('GENDER_Position')
        self::MODLOG_ACTIVE                 => true,
        self::HAS_XPROPS                    => true,
        self::EXPOSE_JSON_API               => true,

        self::JSON_EXPANDER                 => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_PRODUCT_ID                        => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Product::FLD_SUBPRODUCTS        => [],
                    ],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_DOCUMENT_ID               => [
                // needs to be set by concrete model
                self::TYPE                          => self::TYPE_RECORD,
                self::DISABLED                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    //self::MODEL_NAME                    => Sales_Model_Document_Abstract::MODEL_PART_NAME,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_PARENT_ID                 => [
                // needs to be set by concrete model (but will actually be done here in abstract static inherit hook)
                self::TYPE                          => self::TYPE_RECORD,
                self::DISABLED                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    //self::MODEL_NAME                    => Sales_Model_DocumentPosition_Abstract::MODEL_PART_NAME,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_TYPE                      => [
                self::LABEL                         => 'Type', // _('Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DOCUMENT_POSITION_TYPE,
            ],
            self::FLD_POS_NUMBER                => [
                self::LABEL                         => 'Pos.', // _('Pos.')
                self::TYPE                          => self::TYPE_STRING,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_PRODUCT_ID                => [
                self::LABEL                         => 'Product', // _('Product')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Product::MODEL_NAME_PART,
                ],
                self::SHY                           => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_GROUPING                  => [
                self::LABEL                         => 'Grouping', // _('Grouping')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_SORTING                   => [
                self::LABEL                         => 'Sorting', // _('Sorting')
                self::TYPE                          => self::TYPE_INTEGER,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_PRECURSOR_POSITION_MODEL  => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
                self::SHY                           => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_PRECURSOR_POSITION        => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::DISABLED                      => true,
                self::SHY                           => true,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_PRECURSOR_POSITION_MODEL,
                ],
            ],
            self::FLD_TITLE                     => [
                self::LABEL                         => 'Product / Service', // _('Product / Service')
                self::TYPE                          => self::TYPE_STRING,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::LENGTH                        => 255,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_FULLTEXT,
                self::QUERY_FILTER                  => true,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
            ],
            self::FLD_QUANTITY                  => [
                self::LABEL                         => 'Quantity', // _('Quantity')
                self::TYPE                          => self::TYPE_INTEGER,
                self::NULLABLE                      => true,
            ],
            self::FLD_USE_ACTUAL_QUANTITY       => [
                self::LABEL                         => 'Use Actual Quantity', // _('Use Actual Quantity')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_UNIT                      => [
                self::LABEL                         => 'Unit', // _('Unit')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NULLABLE                      => true,
                self::NAME                          => Sales_Config::PRODUCT_UNIT,
            ],
            self::FLD_UNIT_PRICE                => [
                self::LABEL                         => 'Unit Price', // _('Unit Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
            ],
            self::FLD_POSITION_PRICE                 => [
                self::LABEL                         => 'Price', // _('Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_POSITION_DISCOUNT_TYPE    => [
                self::LABEL                         => 'Position Discount Type', // _('Position Discount Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NULLABLE                      => true,
                self::NAME                          => Sales_Config::INVOICE_DISCOUNT_TYPE,
                self::DISABLED                      => true,
                self::SHY                           => true,
            ],
            self::FLD_POSITION_DISCOUNT_PERCENTAGE => [
                self::LABEL                         => 'Position Discount Percentage', // _('Position Discount Percentage')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::NULLABLE                      => true,
                self::DISABLED                      => true,
                self::SHY                           => true,
            ],
            self::FLD_POSITION_DISCOUNT_SUM     => [
                self::LABEL                         => 'Position Discount Sum', // _('Position Discount Sum')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DISCOUNT,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    'singleField'   => true,
                    'price_field'   => self::FLD_POSITION_PRICE,
                    'net_field'     => self::FLD_NET_PRICE
                ],
            ],
            self::FLD_NET_PRICE                 => [
                self::LABEL                         => 'Net price', // _('Net price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SALES_TAX_RATE                 => [
                self::LABEL                         => 'Sales Tax Rate', // _('Sales Tax Rate')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::DEFAULT_VAL_CONFIG            => [
                    self::APP_NAME  => Tinebase_Config::APP_NAME,
                    self::CONFIG => Tinebase_Config::SALES_TAX
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_SALES_TAX               => [
                self::LABEL                         => 'Sales Tax', // _('Sales Tax')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_GROSS_PRICE               => [
                self::LABEL                         => 'Gross Price', // _('Gross Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_COST_BEARER_ID            => [
                self::LABEL                         => 'Cost Bearer', // _('Cost Bearer')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_CostCenter::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_COST_CENTER_ID            => [
                self::LABEL                         => 'Costcenter', // _('Costcenter')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_CostCenter::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    protected static $_exportContextLocale = null;

    public static function setExportContextLocale(?Zend_Locale $locale)
    {
        static::$_exportContextLocale = $locale;
    }

    public static function getExportContextLocale(): ?Zend_Locale
    {
        return static::$_exportContextLocale;
    }

    public function getLocalizedDiscountString(): string
    {
        // exports arrive here with resolved values, so the value of the keyfield, not its id
        switch ($this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
            // exports arrive here with resolved values, so the value of the keyfield, not its id
            case 'Percentage':
            case Sales_Config::INVOICE_DISCOUNT_PERCENTAGE:
                $type = Sales_Config::INVOICE_DISCOUNT_PERCENTAGE;
                break;

            case 'Sum':
            case Sales_Config::INVOICE_DISCOUNT_SUM:
                $type = Sales_Config::INVOICE_DISCOUNT_SUM;
                break;

            default:
                $locale = static::getExportContextLocale() ?: Tinebase_Core::getLocale();
                switch (Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $locale)->getMessageId($this->{self::FLD_POSITION_DISCOUNT_TYPE})) {
                    case 'Percentage':
                        $type = Sales_Config::INVOICE_DISCOUNT_PERCENTAGE;
                        break;
                    case 'Sum':
                        $type = Sales_Config::INVOICE_DISCOUNT_SUM;
                        break;
                    default:
                        return '';
                }
        }

        switch ($type) {
            case Sales_Config::INVOICE_DISCOUNT_PERCENTAGE:
                $value = $this->{self::FLD_POSITION_DISCOUNT_PERCENTAGE};
                $value = round((float)$value, 2);
                return sprintf('%01.2f %%', $value);

            case Sales_Config::INVOICE_DISCOUNT_SUM:
                $value = $this->{self::FLD_POSITION_DISCOUNT_SUM};
                $value = round((float)$value, 2);
                return sprintf('%01.2f ', $value) . Tinebase_Config::getInstance()->get(Tinebase_Config::CURRENCY_SYMBOL);
        }
        return '';
    }

    public function transitionFrom(Sales_Model_DocumentPosition_TransitionSource $transition)
    {
        $source = $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION};
        foreach (static::getConfiguration()->fieldKeys as $property) {
            if ($source->has($property)) {
                $this->{$property} = $source->{$property};
            }
        }

        $this->{self::FLD_PRECURSOR_POSITION_MODEL} =
            $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION_MODEL};
        $this->{self::FLD_PRECURSOR_POSITION} =
            $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION};
        $this->__unset($this->getIdProperty());

        if (!$this->isProduct()) {
            return;
        }

        if ($transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_STORNO}) {
            $this->{self::FLD_UNIT_PRICE} = 0 - $this->{self::FLD_UNIT_PRICE};
            if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
                $this->{self::FLD_POSITION_DISCOUNT_SUM} = 0 - $this->{self::FLD_POSITION_DISCOUNT_SUM};
            }
        }

        // we need to check if there are followup positions for our precursor position already
        $existingQuantities = null;
        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance(static::class);
        foreach ($ctrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(static::class, [
                    ['field' => Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION,
                        'operator' => 'equals', 'value' => $this->{self::FLD_PRECURSOR_POSITION}->getId()],
                    ['field' => Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION_MODEL,
                        'operator' => 'equals', 'value' => $this->{self::FLD_PRECURSOR_POSITION_MODEL}],
                ])) as $existingFollowUp) {
            $existingQuantities += $existingFollowUp->{self::FLD_QUANTITY};
        }

        if (null !== $existingQuantities) {
            $this->canCreatePartialFollowUp();

            if ($existingQuantities >= $this->{self::FLD_QUANTITY}) {
                throw new Tinebase_Exception_Record_Validation('no quantity left for partial followup position');
            }

            $this->{self::FLD_QUANTITY} = $this->{self::FLD_QUANTITY} - $existingQuantities;

            $this->computePrice();
        } elseif ($transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_STORNO}) {
            $this->computePrice();
        }
    }

    public function isProduct(): bool
    {
        return self::POS_TYPE_PRODUCT === $this->{self::FLD_TYPE} ||
            self::POS_TYPE_ALTERNATIVE === $this->{self::FLD_TYPE} ||
            self::POS_TYPE_OPTIONAL === $this->{self::FLD_TYPE};
    }

    protected function canCreatePartialFollowUp(): void
    {
    }

    public function computePrice()
    { // AbstractMixin.computePrice Zeile 62ff
        if (self::POS_TYPE_PRODUCT !== $this->{self::FLD_TYPE}) {
            return;
        }
        $this->{self::FLD_POSITION_PRICE} = $this->{self::FLD_UNIT_PRICE} * $this->{self::FLD_QUANTITY};
        if ($this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
            if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
                $discount = (float)$this->{self::FLD_POSITION_DISCOUNT_SUM};
            } else {
                $discount = ($this->{self::FLD_POSITION_PRICE} / 100) *
                    (float)$this->{self::FLD_POSITION_DISCOUNT_PERCENTAGE};
                $this->{self::FLD_POSITION_DISCOUNT_SUM} = $discount;
            }
        } else {
            $discount = 0;
        }
        $this->{self::FLD_NET_PRICE} = $this->{self::FLD_POSITION_PRICE} - $discount;
        $this->{self::FLD_SALES_TAX} = ($this->{self::FLD_NET_PRICE} / 100) * (float)$this->{self::FLD_SALES_TAX_RATE};
        $this->{self::FLD_GROSS_PRICE} = $this->{self::FLD_NET_PRICE} + $this->{self::FLD_SALES_TAX};
    }

    /**
     * can be reimplemented by subclasses to modify values during setFromJson
     * @param array $_data the json decoded values
     * @return void
     *
     * @todo remove this
     * @deprecated
     */
    protected function _setFromJson(array &$_data)
    {
        parent::_setFromJson($_data);

        unset($_data[self::FLD_PRECURSOR_POSITION]);
        unset($_data[self::FLD_PRECURSOR_POSITION_MODEL]);
    }
}
