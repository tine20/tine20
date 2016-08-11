<?php
/**
 * Expresso Lite
 * Test case that verifies the behavior of the login screen.
 *
 * @package ExpressoLiteTest\Functional\Login
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Login;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;

class LoginTest extends ExpressoLiteTest
{
    /**
     * Checks a valid login attempt
     *
     * CTV3-954
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-954
     *
     * Input data:
     *
     * - valid.user: a valid user login
     * - valid.password: valid password for the user
     */
    public function test_CTV3_954_ValidLogin()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getGlobalValue('user.1.login');
        $VALID_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage->typeUser($VALID_USER);
        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();
        $this->waitForUrl(LITE_URL . '/mail/');

        $this->assertElementPresent('#headlinesArea', 'Headlines listing was not available after successful login');
    }

    /**
     * Checks a login attempt with an invalid user
     *
     * CTV3-955
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-955
     *
     * Input data:
     *
     * - invalid.user: an invalid user login
     * - valid.password: some valid password
     */
    public function test_CTV3_955_InvalidUser()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $INVALID_USER = $this->getTestValue('invalid.user');
        $VALID_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage->typeUser($INVALID_USER);
        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();

        $this->assertAlertTextEquals(
                "Não foi possível efetuar login.\nO usuário ou a senha estão incorretos.",
                'Problems with incorrect user message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt with a valid user, but with the wrong password
     *
     * CTV3-955
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-955
     *
     * Input data:
     *
     * - valid.user: a valid user login
     * - invalid.password: wrong password for the user
     */
    public function test_CTV3_955_InvalidPassword()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getGlobalValue('user.1.login');
        $INVALID_PASSWORD = $this->getTestValue('invalid.password');

        $loginPage->typeUser($VALID_USER);
        $loginPage->typePassword($INVALID_PASSWORD);

        $loginPage->clickLogin();

        $this->assertAlertTextEquals(
                "Não foi possível efetuar login.\nO usuário ou a senha estão incorretos.",
                'Problems with incorrect password message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt where only the password is typed,
     * but not the user login
     *
     * CTV3-954
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-954
     *
     * Input data:
     *
     * - valid.password: some random password
     */
    public function test_CTV3_954_NoUser()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();
        $this->assertAlertTextEquals(
                "Por favor, digite seu nome de usuário.",
                'Problems with user not informed message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt where only the user login is typed,
     * but not the password
     *
     * CTV3-954
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-954
     *
     * - valid.user: a valid user login
     */
    public function test_CTV3_954_NoPassword()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getGlobalValue('user.1.login');

        $loginPage->typeUser($VALID_USER);

        $loginPage->clickLogin();
        $this->assertAlertTextEquals(
                "Por favor, digite sua senha.",
                'Problems with password not informed message');
        $this->dismissAlert();
    }
}
