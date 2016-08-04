<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite login screen
 *
 * @package ExpressoLiteTest\Functional\Login
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Login;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class LoginPage extends GenericPage
{
    /**
     * Types a value in the user field
     *
     * @param string $user The value to be typed into the field
     */
    public function typeUser($user)
    {
        $this->byCssSelector('#user')->value($user);
    }

    /**
     * Types a value in the password field
     *
     * @param string $password The value to be typed into the field
     */
    public function typePassword($password)
    {
        $this->byCssSelector('#pwd')->value($password);
    }

    /**
     * Clicks the login button
     */
    public function clickLogin()
    {
        $this->byCssSelector('#btnLogin')->click();
    }

    /**
     * This will perform a login into the system, involving the following steps:
     * 1 - clear user field, 2- type a user, 3 - type a password,
     * 4 - click loggin button, 5 - Wait for the mail module to be loaded
     *
     * @param string $user The user to be used for the login
     * @param string $pwd The password to be used for the login
     */
    public function doLogin($user, $pwd)
    {
        $this->byCssSelector('#user')->clear();
        $this->typeUser($user);
        $this->typePassword($pwd);
        $this->clickLogin();
        $this->testCase->waitForUrl(LITE_URL . '/mail/');
        $this->testCase->waitForAjaxAndAnimations();
    }
}
