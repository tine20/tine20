<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Addressbook_Backend_Sync_Ldap
 */
class Addressbook_LdapSyncTest extends TestCase
{

    protected $_oldSyncBackendsConfig = array();

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    protected $_ldapBaseDN = 'ou=ab,dc=example,dc=org';

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $ldapOptions = Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKEND)->toArray();

        $this->_oldSyncBackendsConfig = Addressbook_Config::getInstance()->get('syncBackends');
        Addressbook_Config::getInstance()->set('syncBackends', array(
            '0' => array(
                'class'     => 'Addressbook_Backend_Sync_Ldap',
                'options'   => array(
                    /* 'attributesMap' => array(
                        'n_fn' => 'commonName',
                        'n_family' => 'surname',
                    ), */
                    'baseDN' => $this->_ldapBaseDN,
                    'ldapConnection' => $ldapOptions
                ),
                'filter'    => array(
                    array('field' => 'query', 'operator' => 'contains', 'value' => 'test')
                )
            )
        ));

        Addressbook_Controller_Contact::getInstance()->resetSyncBackends();

        $this->_ldap = new Tinebase_Ldap($ldapOptions);
    }

    /**
     * tear down tests
     */
    protected function tearDown()
    {
        Addressbook_Config::getInstance()->set('syncBackends', $this->_oldSyncBackendsConfig);
        Addressbook_Controller_Contact::getInstance()->resetSyncBackends();

        parent::tearDown();
    }

    protected function _checkUIDinBaseDN($uid, $presence = true)
    {
        $filter = Zend_Ldap_Filter::equals(
            'uid', Zend_Ldap::filterEscape($uid)
        );

        $result = $this->_ldap->search($filter, $this->_ldapBaseDN);

        if (($presence === true && $result->count() > 0) ||
            ($presence === false && $result->count() === 0)) {
            return true;
        }

        return false;
    }

    /**
     * test sync to sync backend works, we need to match filter {n_fn contains "test"}
     *
     * @group nogitlabci_ldap
     */
    public function testSyncBackend()
    {
        $contactData = array(
            'n_given'       => 'testGiven',
            'n_family'      => 'testFamily',
        );

        // this contact should be in the sync backend now
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact($contactData));
        $this->assertTrue($this->_checkUIDinBaseDN($contact->getId()), "did not find newly created contact in sync backend");

        $contact->n_given = 'given';
        $contact->n_family = 'family';

        // now the contact should be removed from the sync backend
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        $this->assertTrue($this->_checkUIDinBaseDN($contact->getId(), false), "did find modified contact in sync backend, though it doesnt match the filter");

        $contact->n_given = 'test';

        // now the contact should be added the sync backend again
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        $this->assertTrue($this->_checkUIDinBaseDN($contact->getId()), "did not find modified created contact in sync backend, though it should be there");

        // now the contact should be removed from the sync backend again
        Addressbook_Controller_Contact::getInstance()->delete($contact);
        $this->assertTrue($this->_checkUIDinBaseDN($contact->getId(), false), "did find contact in sync backend, though it was deleted");

        // test users
    }
}