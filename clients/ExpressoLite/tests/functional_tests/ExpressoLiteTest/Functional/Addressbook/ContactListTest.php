<?php
/**
 * Expresso Lite
 * This tests verify the contact list in contact's module
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Login\LoginPage;
use ExpressoLiteTest\Functional\Mail\MailPage;

class ContactListTest extends ExpressoLiteTest
{
    /*
     * Check if corporate catalog has Letter Separator and entry cards with
     * Names and email address
     *
     * CTV3-904
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-904;
     */
    public function test_Ctv3_904_List_Corporate()
    {
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');
        $COMMON_TEXT_FRAGMENT = $this->getTestValue('common.text.fragment');
        $INEXISTENT_TEXT_FRAGMENT = $this->getTestValue('inexistent.text.fragment');
        $MORE_RESULT_TEXT_FRAGMENT = $this->getTestValue('more.result.text.fragment');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $mailPage->typeSearchText($COMMON_TEXT_FRAGMENT);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $entries = $addressbookPage->getCatalogEntryByName($COMMON_TEXT_FRAGMENT);
        $this->assertEquals(1, count($entries),
                'There are diferent number of catalog entries after find criteria applied');

        $mailPage->clearSearchField();
        $mailPage->typeSearchText($INEXISTENT_TEXT_FRAGMENT);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $entries = $addressbookPage->getCatalogEntryByName($INEXISTENT_TEXT_FRAGMENT);
        $this->assertEquals(0, count($entries),
                'The total of contacts differs from zero after find criteria applied');

        $mailPage->clearSearchField();
        $mailPage->typeSearchText($MORE_RESULT_TEXT_FRAGMENT);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();
        $entries = $addressbookPage->getCatalogEntryByName($MORE_RESULT_TEXT_FRAGMENT);

        for ($i = 0; $i < count($entries) - 1; $i++)
        {
            $this->assertTrue(
                    strcmp(strtolower($entries[i]->getNameFromContact()),
                           strtolower($entries[i+1]->getNameFromContact())) < 0,
                    'There are two contacts in incorrect order');
        }
        $this->assertTrue($addressbookPage->hasLoadMoreButton(), 'The button of load more contacts does not visible, maybe does not has fifty or more contacts');
        $this->assertGreaterThanOrEqual(50, $addressbookPage->getCounterTotal(),
                'There are fewer contacts than expected after find criteria applied ');
    }

    /*
     * Check if personal catalog has Letter Separator and entry cards with
     * Names and email address
     *
     * CTV3-905
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-905;
     */
    public function test_Ctv3_905_List_Personal()
    {
        //load test data
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_EMAIL = $this->getTestValue('contact.1.mail');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $CONTACT_2_EMAIL = $this->getTestValue('contact.2.mail');
        $CONTACT_2_NAME = $this->getTestValue('contact.2.name');
        $LETTER_1 = $this->getTestValue('letter.1');
        $LETTER_2 = $this->getTestValue('letter.2');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);

        //This sending the e-mail is to generate a personal contact to be
        //used in test
        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($CONTACT_1_EMAIL, $CONTACT_2_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER_1), "Letter separator '$LETTER_1' should have been displayed, but it was not");
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER_2), "Letter separator '$LETTER_2' should have been displayed, but it was not");

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);

        $this->assertEquals($CONTACT_1_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$CONTACT_1_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_2_NAME);

        $this->assertEquals($CONTACT_2_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$CONTACT_2_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($CONTACT_1_EMAIL);

        $this->assertEquals($CONTACT_1_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$CONTACT_1_EMAIL', the recipient email is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($CONTACT_2_EMAIL);

        $this->assertEquals($CONTACT_2_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$CONTACT_2_EMAIL', the recipient email is not created in personal catalog ");
    }

    /*
     * Check if personal catalog has information about contacts;
     *
     * CTV3-906
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-906;
     */
    public function test_Ctv3_906_List_Personal_Contact_Detail()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_EMAIL = $this->getTestValue('contact.1.mail');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $CONTACT_1_COMPANY = $this->getTestValue('contact.1.company');
        $FIELD_COMPANY = $this->getTestValue('field.company');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($CONTACT_1_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickAddressbook();

        $addressbookPage = new AddressbookPage($this);
        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();

        $mailPage->typeSearchText($CONTACT_1_EMAIL);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);
        $contactListItem->click();
        $this->waitForAjaxAndAnimations();

        $widgetContactDetails = $addressbookPage->getWidgetContactDetails();
        $this->assertEquals($CONTACT_1_NAME, $widgetContactDetails->getName(),
                "There is not entry catalog name '$CONTACT_1_NAME', the recipient name is not created in personal catalog ");

        $this->assertEquals($CONTACT_1_EMAIL, $widgetContactDetails->getEmail(),
                "There is not entry catalog E-mail '$CONTACT_1_EMAIL', the recipient email is not created in personal catalog ");

        $this->assertTrue($widgetContactDetails->hasImage(),
                "Image from user of catalog should have been displayed, but it was not");

        $this->assertEquals($CONTACT_1_COMPANY, $widgetContactDetails->getOtherFieldValue($FIELD_COMPANY),
                "There is not entry catalog cooporate '$CONTACT_1_COMPANY', the company is not created in corporate catalog ");
    }

    /*
     * Check if  corporated catalog has information about contacts;
     *
     * CTV3-906
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-906;
     */
    public function test_Ctv3_906_List_Corporate_Contact_Detail()
    {

        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_EMAIL = $this->getTestValue('contact.1.mail');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $CONTACT_1_PHONE = $this->getTestValue('contact.1.phone');
        $CONTACT_1_COMPANY = $this->getTestValue('contact.1.company');
        $CONTACT_1_DEPARTMENT = $this->getTestValue('contact.1.department');
        $CONTACT_1_STATE = $this->getTestValue('contact.1.state');
        $CONTACT_1_ZIP = $this->getTestValue('contact.1.zip');
        $FIELD_COMPANY = $this->getTestValue('field.company');
        $FIELD_DEPARTMENT = $this->getTestValue('field.department');
        $FIELD_STATE = $this->getTestValue('field.state');
        $FIELD_ZIP = $this->getTestValue('field.zip');
        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $addressbookPage = new AddressbookPage($this);
        $mailPage->clickAddressbook();
        $addressbookPage->clickCorporateCatalog();
        $this->waitForAjaxAndAnimations();

        $mailPage->clearSearchField();
        $mailPage->typeSearchText($CONTACT_1_NAME);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);
        $contactListItem->click();
        $this->waitForAjaxAndAnimations();

        $widgetContactDetails = $addressbookPage->getWidgetContactDetails();
        $this->assertEquals($CONTACT_1_NAME, $widgetContactDetails->getName(),
                "There is not entry catalog name '$CONTACT_1_NAME', the recipient name is not created in personal catalog ");

        $this->assertEquals($CONTACT_1_EMAIL, $widgetContactDetails->getEmail(),
                "There is not entry catalog E-mail '$CONTACT_1_EMAIL', the recipient email is not created in personal catalog ");

        $this->assertTrue($widgetContactDetails->hasImage(),
                "Image from user of catalog should have been displayed, but it was not");

        $this->assertEquals($CONTACT_1_PHONE, $widgetContactDetails->getPhone(),
                "There is not entry catalog Phone '$CONTACT_1_PHONE', the phone is not created in corporate catalog ");

        $this->assertEquals($CONTACT_1_COMPANY, $widgetContactDetails->getOtherFieldValue($FIELD_COMPANY),
                "There is not entry catalog cooporate '$CONTACT_1_COMPANY', the company is not created in corporate catalog ");

        $this->assertEquals($CONTACT_1_DEPARTMENT, $widgetContactDetails->getOtherFieldValue($FIELD_DEPARTMENT),
                "There is not entry catalog department '$CONTACT_1_DEPARTMENT', the department is not created in corporate catalog ");

        $this->assertEquals($CONTACT_1_STATE, $widgetContactDetails->getOtherFieldValue($FIELD_STATE),
                "There is not entry catalog state '$CONTACT_1_STATE', the state is not created in corporate catalog ");

        $this->assertEquals($CONTACT_1_ZIP, $widgetContactDetails->getOtherFieldValue($FIELD_ZIP),
                "There is not entry catalog zip '$CONTACT_1_ZIP', the zip is not created in corporate catalog ");
    }

    /*
     * Check if the fields in corporate contacts do not allow overwrite
     *
     * CTV3-1026
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1026;
     */
    public function test_Ctv3_1026_Check_Edit_Corporate_Contact()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $FIELD_COMPANY = $this->getTestValue('field.company');
        $FIELD_DEPARTMENT = $this->getTestValue('field.department');
        $FIELD_STATE = $this->getTestValue('field.state');
        $FIELD_ZIP = $this->getTestValue('field.zip');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickCorporateCatalog();
        $this->waitForAjaxAndAnimations();

        $mailPage->clearSearchField();
        $mailPage->typeSearchText($CONTACT_1_NAME);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);
        $contactListItem->click();
        $this->waitForAjaxAndAnimations();
        $widgetContactDetails = $addressbookPage->getWidgetContactDetails();

        $this->assertEquals('true',$widgetContactDetails->isReadonlyField($FIELD_COMPANY),
                "The field '$FIELD_COMPANY' is not readonly");

        $this->assertEquals('true',$widgetContactDetails->isReadonlyField($FIELD_DEPARTMENT),
                "The field '$FIELD_DEPARTMENT' is not readonly");

        $this->assertEquals('true',$widgetContactDetails->isReadonlyField($FIELD_STATE),
                "The field '$FIELD_STATE' is not readonly");

        $this->assertEquals('true',$widgetContactDetails->isReadonlyField($FIELD_ZIP),
                "The field '$FIELD_ZIP' is not readonly");
    }

    /*
     * Check if the fields in personal contacts do not allow overwrite
     *
     * CTV3-1027
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1027;
     */
    public function test_Ctv3_1027_Check_Edit_Personal_Contact()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_EMAIL = $this->getTestValue('contact.1.mail');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $FIELD_COMPANY = $this->getTestValue('field.company');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);
        //Sending an e-mail to generate the personal contact to be used in the test
        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($CONTACT_1_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);

        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();

        $mailPage->clearSearchField();
        $mailPage->typeSearchText($CONTACT_1_NAME);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);
        $contactListItem->click();
        $this->waitForAjaxAndAnimations();
        $widgetContactDetails = $addressbookPage->getWidgetContactDetails();

        // Checking other fields will only be possible when the system allows personal contact edition
        $this->assertEquals('true', $widgetContactDetails->isReadonlyField($FIELD_COMPANY),
                "The field '$FIELD_COMPANY' is not readonly");
        }
}