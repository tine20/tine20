<?php
/**
 * Expresso Lite
 * Represents a Expresso Lite test case that will use a single login
 * for all tests. This speeds the execution of the tests considerably.
 *
 * @package ExpressoLiteTest\Functional\Generic
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

class SingleLoginTest extends ExpressoLiteTest
{
    /**
     * @var boolean Indicates if the login was already performed in this test case
     */
    protected static $isLoggedIn;

    /**
     * Overrides ExpressoLiteTest::setUpPage. This method will perform login into
     * Expresso Lite, but only if it was not already done in this test case.
     *
     * @see \ExpressoLiteFunctionalTests\Generic\ExpressoLiteTest::setUpPage()
     */
    public function setUpPage()
    {
        $this->setClearSessionDataBetweenTests(false);
        if (!self::$isLoggedIn) {
            $this->prepareSession()->cookie()->clear();
            parent::setUpPage();
            $this->doLoginWithIniFileValues();
        } else {
            parent::setUpPage($this->getTestUrl());
            $this->waitForAjaxAndAnimations();
        }
    }

    /**
     * Performs the login steps in the current window. The user and password to be
     * used will be searched in the global test data.
     */
    private function doLoginWithIniFileValues()
    {
        $user = $this->testData->getGlobalValue('user.'.$this->getUserNumber().'.login');
        $password = $this->testData->getGlobalValue('user.'.$this->getUserNumber().'.password');

        $this->doLogin($user, $password);
        self::$isLoggedIn = true;
    }

    /**
     * Overrides PHPUnit onNotSuccessfulTest. As failed tests reset the selenium session,
     * this methods will also reset $isLoggedIn value to ensure a new login is made in
     * Expresso Lite on the next test to be executed.
     *
     * @param unknown $e The exception that made the test fail.
     */
    public function onNotSuccessfulTest($e)
    {
        self::$isLoggedIn = false;
        parent::onNotSuccessfulTest($e);
    }

    /**
     * Returns the URL to be used when the user starts a new test after the login
     * was made. This is meant to be overwritten in subclasses
     *
     * @return string The URL to be used in the begining of each test
     */
    public function getTestUrl()
    {
        return LITE_URL;
    }

    /**
     * Returns the user number to be used for each login. (1, 2, etc...).
     * This user login and password will be searched in the global test data file
     * using the following format: user.1.login and user.1.password.
     * This method is supposed to be overwritten in subclasses
     *
     * @return int The number to be used in the begining of each test
     */
    public function getUserNumber()
    {
        return 1;
    }

    /**
     * Overrides PHPUnit's setUpPage. This method will assure that a new login
     * will be done in the beginning of a SingleLoginTest
     */
    public static function setUpBeforeClass()
    {
        self::$isLoggedIn = false;
    }
}
