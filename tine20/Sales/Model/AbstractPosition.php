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
class Sales_Model_AbstractPosition extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AbstractPosition';

    const FLD_ID = 'id';
    const FLD_CONFIG = 'config';
    const FLD_CONFIG_CLASS = 'config_class';
    const FLD_MFA_CONFIG_ID = 'mfa_config_id';
    const FLD_NOTE = 'note';
    
    const FLD_DOCUMENT_ID = 'document_id';
    const FLD_SORT = 'sort'; // automatisch in 10000er schritten, shy
    const FLD_GROUPING = 'grouping'; // gruppierte darstellung, automatische laufende nummern pro gruppe(nicht persistiert)
    
    const FLD_SUBPRODUCT_MAPPING = 'subproduct_mapping'; // "kreuztabelle" Sales_Model_SubproductMapping (nur für bundles nicht für set's?)
    
    const FLD_REFERENCE_POSITION = 'reference_position'; // z.B. angebotsposition bei auftragsposition (virtual, link?)
    
    const FLD_PRODUCT_ID = 'product_id';  // optional, es gibt auch textonlypositionen
    
    const FLD_TITLE = 'title'; // einzeiler/überschrift(fett) aus product übernommen sind änderbar
    const FLD_DESCRIPTION = 'description'; // aus product übernommen sind idr. änderbar
    const FLD_QUANTITY = 'quantity'; // Anzahl - aus produkt übernehmen, standard 1
    const FLD_USE_ACTUAL_QUANTITY  = 'use_actual_quantity'; // boolean, wenn true muss es eine verknüpfung mit n leistungsnachweisen (accountables) geben
    const FLD_UNIT = 'unit'; // Einheit - aus product übernehmen
    const FLD_UNIT_PRICE = 'unit_price'; // Einzelpreis - aus product übernehmen
    const FLD_NET_PRICE = 'net_price'; // Nettopreis - anzahl * einzelpreis

    const FLD_POSITION_DISCOUNT_TYPE = 'position_discount_type'; // PERCENTAGE|SUM
    const FLD_POSITION_DISCOUNT_PERCENTAGE = 'position_discount_percentage'; // automatische Berechnung je nach tupe
    const FLD_POSITION_DISCOUNT_SUM = 'position_discount_sum'; // automatische Berechnung je nach type
    
    const FLD_TAX_RATE = 'tax_rate'; // Mehrwertssteuersatz - berechnen
    const FLD_SALES_TAX = 'sales_tax'; // Mehrwertssteuer
    const FLD_GROSS_PRICE= 'gross_price'; // Bruttopreis - berechnen

    const FLD_COST_CENTER_ID = 'payment_cost_center_id'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat und ob user überschreiben kann
    const FLD_COST_BEARER_ID = 'payment_cost_bearer_id'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat, und ob user überschreiben kann

    const FLD_XPROPS = 'xprops'; // z.B. entfaltungsart von Bundle od. Set merken
    

    
    // Produkte:
    // - shortcut intern (wird als kurzbez. in subproduktzuordnung übernommen, in pos nicht benötigt) - varchar 20
    // - title - varchar
    // - description - text
    // - gruppierung (Gruppierung von Positionen z.B. A implementierung, B regelm., C zusatz) - varchar - autocomplete
    // - anzahl
    // - anzahl aus accounting (ja/nein)
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
    //      - keine: inclusiveprodukt bekommt keine eigene belegposition (z.B. nur teil des positionstextes des Hauptproduktes, keine variable/zusatz anzahl in folge belegen (z.B. lieferschein/rechnung) möglich)
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

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
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
