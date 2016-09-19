<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Phone_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     *
     * @return void
     */
    public function update_0()
    {
        // we need at least addressbook version 9,7
        if (version_compare($this->getApplicationVersion('Addressbook'), '9.8') < 0 ) {
            return;
        }

        $setupUser = static::getSetupFromConfigOrCreateOnTheFly();

        if ($setupUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);

            $filter = new Phone_Model_CallFilter(array(array(
                'field' => 'start',
                'operator' => 'after',
                'value' => date('Y-m-d H:i:s', time() - 3600 * 24 * 30 * 3)) // <= 3 month back
            ), 'AND', array('ignoreAcl' => true));

            $addressbookController = Addressbook_Controller_Contact::getInstance();
            $phoneController = Phone_Controller_Call::getInstance();
            $calls = $phoneController->search($filter);

            foreach ($calls as $_record) {
                // resolve telephone number to contacts if possible
                $telNumber = Addressbook_Model_Contact::normalizeTelephoneNoCountry(
                    $phoneController->resolveInternalNumber($_record->destination));

                if (null === $telNumber)
                    continue;

                $filter = new Addressbook_Model_ContactFilter(array(
                    array('field' => 'telephone_normalized', 'operator' => 'equals', 'value' => $telNumber),
                ));

                $contacts = $addressbookController->search($filter);
                $relations = array();

                foreach ($contacts as $contact) {
                    $relations[] = array(
                        'related_model' => 'Addressbook_Model_Contact',
                        'related_id' => $contact->getId(),
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'type' => 'CALLER',
                    );
                }

                if (count($relations) > 0) {
                    $_record->relations = $relations;
                    $phoneController->update($_record);
                }
            }
        }

        $this->setApplicationVersion('Phone', '9.1');
    }
}
