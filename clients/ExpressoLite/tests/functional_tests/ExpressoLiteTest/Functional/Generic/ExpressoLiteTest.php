<?php
/**
 * Expresso Lite
 * Generic abstract class for Expresso Lite functional tests.
 *
 * @package ExpressoLiteTest\Functional\Generic
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

use ExpressoLiteTest\Functional\Login\LoginPage;

abstract class ExpressoLiteTest extends \PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * @var string DEFAULT_BROWSER The default browser to be used for the tests
     */
    CONST DEFAULT_BROWSER = 'firefox';

    /**
     * @var int IMPLICIT_WAIT If a user selects a DOM element that is not yet available at the browser window,
     * this is the max number of milliseconds that selenium will wait for the element to be present
     * before throwing an exception
     */
    const IMPLICIT_WAIT = 30000;

    /**
     * @var int DEFAULT_WAIT_INTERVAL The interval to be waited between attempts to check if an
     * element is present
     */
    const DEFAULT_WAIT_INTERVAL = 1000;

    /**
     * @var boolean $clearSessionDataBetweenTests Variable that indicates if the PHP
     * session should be cleared between each test of the current test case
     */
    private $clearSessionDataBetweenTests;

    /**
     * @var TestData $testData The data associated with the current test case
     */
    protected $testData;

    /**
     * @var string $uniqueId A unique random id for the test. This is useful to
     * generate unique strings for test data
     */
    protected $uniqueId;

    /**
     * Overrides PHP Unit setUp method. In Selenium tests, this method must
     * define the browser and the base URL to be used in this test case
     */
    protected function setUp()
    {
        $this->setBrowser(self::DEFAULT_BROWSER);
        // this may be changed in the future to allow testing in diferent browsers

        $this->setBrowserUrl(LITE_URL);
        $this->clearSessionDataBetweenTests = true;
        $this->testData = new TestData($this);
    }

    /**
     * Overrides PHPUnit_Extensions_Selenium2TestCase::setUpPage. This method will
     * setup the implicit wait time and navigate to the URL to be tested.
     *
     * @param string $initialUrl URL where Expresso Lite should be tested. If none is informed,
     * it uses the LITE_URL constant
     */
    public function setUpPage($initialUrl = '/')
    {
        if ($this->clearSessionDataBetweenTests) {
            $this->prepareSession()->cookie()->clear();
        }

        $this->uniqueId = uniqid();
        $this->restoreImplicitWaitTime();
        $this->url($initialUrl);

        usleep(500000);
        //This is used to wait for the initial animation on the login screen,
        //and has shown to be more reliable than other tested alternatives
    }

    /**
     * Returns a unique string id that is generated in the begining of each test
     *
     * @return string The test unique id
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * Retrieves a value from the test data file. The section within the .ini file
     * is chosen automatically based on the name of the test currently being executed.
     *
     * @param string $key The key to the test value
     *
     * @return string The value associated with the key.
     */
    public function getTestValue($key)
    {
        $sectionName = $this->getName(); //gets the name of the test method being currently executed
        return $this->testData->getTestValue($sectionName, $key);
    }

    /**
     * Retrieves a value from the global test data file.
     *
     * @param string $key The key to the test value
     *
     * @return string The value associated with the key.
     */
    public function getGlobalValue($key)
    {
        return $this->testData->getGlobalValue($key);
    }

    /**
     * Asserts that a specific DOM element is present in the current page. It will try
     * to search the element once per DEFAULT_WAIT_INTERVAL milliseconds for $timeout seconds.
     *
     * @param string $cssSelector The CSS selector of the element that must be present
     * @param string $message The fail message to be used if the assertion fails
     * @param number $timeout The max milliseconds to wait for the element to be present
     */
    public function assertElementPresent($cssSelector, $message = '', $timeout = self::IMPLICIT_WAIT)
    {

        for ($i=0; $i < $timeout; $i+=self::DEFAULT_WAIT_INTERVAL) {
            try {
                $this->byCssSelector($cssSelector);
                return;
            } catch (\Exception $e) {
                //means its not present yet
                //let's wait a little longer an try again
                usleep(self::DEFAULT_WAIT_INTERVAL);
            }
        }
        $this->fail("Element '$cssSelector' not found. $message");
    }

    /**
     * Asserts that there is an alert showing a specific message.
     * If no alert is present, or if the message differs from the expected,
     * the assertion fails.
     *
     * @param string $expected The expected message to be shown in the alert
     * @param string $message The message if the assertion values
     */
    public function assertAlertTextEquals($expected, $message='')
    {
        $this->waitForAlert();
        $this->assertEquals($expected, $this->alertText(), $message);
    }

    /**
     * This function will make the test wait for any pending operations
     * (animations, ajax calls or visible throbbers). Two verifications
     * are performed: one as soon this method is called and a second one
     * after a small interval. This is done to ensure that the browser
     * has time to do any processing in between user interaction and the
     * operation (ajax, etc...) itself.
     */
    public function waitForAjaxAndAnimations()
    {
        $this->doSingleWait();
        usleep(250000);
        $this->doSingleWait();
    }

    /**
     * This function will make the test wait for the following conditions:
     *   a) There are AJAX call waiting for response;
     *   b) There are no animations being executed
     *   c) There are no throbbers visible on screen
     *
     * This wait will timeout after self::IMPLICIT_WAIT seconds. In this case,
     * an exception is thrown indicating what condition was not met.
     */
    private function doSingleWait()
    {
        try {
            $this->waitUntil(function($testCase) {
                $activeElements = $testCase->execute(array(
                        'script' =>
                        'return require("common-js/App").getNumberOfPendingAjax() + ' . //number of pending ajax calls
                        '$(\'.velocity-animating\').length + ' . //number of currently animating elements
                        '$(\'*[id*="hrobber"]:visible, *[class*="hrobber"]:visible, img[src $= "chromiumthrobber.svg"]:visible\').length;', //number of visible thobbers
                        'args' => array()
                )); //number of pending ajax calls + number of currently animating elements
                return $activeElements > 0 ? null : true;
            }, self::IMPLICIT_WAIT);
        } catch (\Exception $exc) {
            $pendingAjax = $this->execute(array(
                    'script' => 'return require("common-js/App").getNumberOfPendingAjax();',
                    'args' => array()
            ));

            $animated = $this->execute(array(
                    'script' => 'return JSON.stringify($(".velocity-animating"));',
                    'args' => array()
            ));

            $thobber = $this->execute(array(
                    'script' => 'return JSON.stringify($(\'*[id*="hrobber"]:visible, *[class*="hrobber"]:visible, img[src $= "chromiumthrobber.svg"]:visible\'));',
                    'args' => array()
            ));

            throw new \Exception("waitForAjaxAndAnimations failed.\n" .
                    "Number of pending Ajax: $pendingAjax\n" .
                    "Animated elements: ' .  $animated\n" .
                    "Thobbers: " . $thobber,
                    0, $exc);
        }
    }

    /**
     * Waits for an alert to be displayed.
     *
     * @param int $timeout The max number of milliseconds to wait for the alert
     *
     * @throws \Exception If no alert is displayed within the specificied timeout,
     * an exception is thrown
     */
    public function waitForAlert($timeout=self::IMPLICIT_WAIT)
    {
        $text = null;
        for ($i=0; $i < $timeout; $i+=self::DEFAULT_WAIT_INTERVAL) {
            try {
                $text = $this->alertText();
                break;
            } catch (\Exception $e) {
                usleep(self::DEFAULT_WAIT_INTERVAL);
            }
        }

        if ($text == null) {
            throw new \Exception('Waited for an alert to show but no alert was present');
        }
    }

    /**
     * This function makes the test wait until a specific URL is shown
     * in the current browser window
     *
     * @param string $expectedUrl The URL to wait for
     */
    public function waitForUrl($expectedUrl)
    {
        $this->waitUntil(
                function($testCase) use ($expectedUrl) {
                    return $testCase->url() == $expectedUrl ? 1 : null;
                },
                self::IMPLICIT_WAIT // milliseconds to wait before giving up if the URL hasn't changed
        );
    }

    /**
     * Sets the behavior for browser session data between tests. If this is set
     * to true, all session data is reseted before each test
     *
     * @param boolean $clearSessionDataBetweenTests
     */
    public function setClearSessionDataBetweenTests($clearSessionDataBetweenTests)
    {
        $this->clearSessionDataBetweenTests = $clearSessionDataBetweenTests;
    }

    /**
     * Checks if a specified element is present and displayed in the current test case
     *
     * @param unknown $element The DOM element returned by PHPUnit selenium to be checked
     * @return boolean True if the element is present and visible in the current window,
     * false otherwise
     */
    public function isElementDisplayed($element)
    {
        $this->reduceImplicitWait();

        $displayed = false;
        try {
            $displayed = $element->displayed();
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            // We assume any problems here mean that the element is not displayed.
            // This often happens because of element staleness
        }

        $this->restoreImplicitWaitTime();
        return $displayed;
    }

    /**
     * Performs a login operation. This is just a shortcut to avoid having to add
     * LoginPage in tests in which it is not really necessary
     */
    public function doLogin($user, $password) {
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($user, $password);
    }

    /**
     * Temporarily reduces implicit wait time. This is useful when we want ensure some element
     * is NOT present in the screen, because, by default, the test will wait for all the the
     * implicit wait time to be sure that the elements is really no there.
     */
    public function reduceImplicitWait($waitTime = 1000)
    {
        $this->timeouts()->implicitWait($waitTime);
    }

    /**
     * Restores implicit wait time to it default value. This should usually used after
     * reduceImplicitWait.
     */
    public function restoreImplicitWaitTime()
    {
        $this->timeouts()->implicitWait(self::DEFAULT_WAIT_INTERVAL);
    }
}
