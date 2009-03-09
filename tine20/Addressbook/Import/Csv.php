<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        remove obsolete code
 *

/**
 * csv import class for the addressbook
 * 
 * for the use of the mapping parameter => see tests/tine20/Addressbook/Import/CsvTest 
 *
 * @package     Addressbook
 * @subpackage  Import
 * 
 * a sample mapping:
 * --
 * array(
    'mapping' => array(
        'adr_one_locality'      => 'Ort',
        'adr_one_postalcode'    => 'Plz',
        'adr_one_street'        => 'Straße',
        'org_name'              => 'Name1',
        'org_unit'              => 'Name2',
        'note'                  => array(
            'Mitarbeiter'           => 'inLab Spezi',
            'Anzahl Mitarbeiter'    => 'ANZMitarbeiter',
            'Bemerkung'             => 'Bemerkung',
        ),
        'tel_work'              => 'TelefonZentrale',
        'tel_cell'              => 'TelefonDurchwahl',
        'n_family'              => 'Nachname',
        'n_given'               => 'Vorname',
        'n_prefix'              => array('Anrede', 'Titel'),
        'container_id'                 => array(
            'inLab Spezi'           => array(
            'Name 1'                 => 92,
            'Name 2'                 => 66,
            'Name 3'                 => 88
            ),
        ),
    ),
    //'containerId' => 2,
 *
 */
class Addressbook_Import_Csv extends Tinebase_Import_Csv_Abstract
{
}
