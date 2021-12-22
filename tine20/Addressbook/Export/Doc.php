<?php
/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_Doc extends Tinebase_Export_Doc
{
    protected $_defaultExportname = 'adb_default_doc';

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        return parent::_getTwigContext($context + [
                'address' => $this->getAddress($context['record'])
            ]
        );
    }

    /**
     * @param Addressbook_Model_Contact $record
     * @return array
     *
     * @todo move to Addressbook_Model_Contact::getPreferredAddress
     */
    protected function getAddress($record)
    {
        if (!($record instanceof Addressbook_Model_Contact)) {
            return;
        }
        
        switch ($record->preferred_address) {
            // Private
            case '1':
                $address = [
                    'company' => '',
                    'firstname' => $record->n_given,
                    'lastname' => $record->n_family,
                    'street' => $record->adr_two_street,
                    'postalcode' => $record->adr_two_postalcode,
                    'locality' => $record->adr_two_locality
                ];
                break;
            // Business
            case '0':
            default:
                $address = [
                    'company' => $record->org_name,
                    'firstname' => $record->n_given,
                    'lastname' => $record->n_family,
                    'street' => $record->adr_one_street,
                    'postalcode' => $record->adr_one_postalcode,
                    'locality' => $record->adr_one_locality
                ];
        }

        return $address;
    }
}
