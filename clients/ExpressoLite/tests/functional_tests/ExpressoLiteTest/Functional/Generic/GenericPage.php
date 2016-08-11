<?php
/**
 * Expresso Lite
 * Generic abstract class Page Objects to be used in the test cases.
 * The page objects may refer to the whole window or to specific
 * sessions within the window.
 *
 * @package ExpressoLiteTest\Functional\Generic
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

class GenericPage
{
    /**
     * @var ExpressoLiteTest $testCase The testCase in which the
     * page object is being used
     */
    protected $testCase;

    /**
     * @var unknown $rootContext The root element in which the
     * page object is based. If the page object refers to the whole
     * window, it will be the test case itself. If the page object
     * refers only to a part of the window, $rootContext will be
     * a reference to the root DOM element of that part of the window
     */
    protected $rootContext;

    /**
     * Creates a new GenericPage object. If no root context is informed,
     * it will use the whole test case as root context.
     *
     * @param ExpressoLiteTest $testCase The test case to which this
     * page object belongs
     * @param string $rootContext The root context in which operations
     * in the page object are based. @see GenericPage::rootContext for
     * more information
     */
    public function __construct($testCase, $rootContext = null)
    {
        $this->testCase = $testCase;
        $this->rootContext = ($rootContext !== null) ?
            $rootContext :
            $testCase;
        // if no root context is informed, we assume it is
        // the test case main window
    }

    /**
     * Returns the test case to which this page object belongs
     *
     * @return \ExpressoLiteTest\Functional\Generic\ExpressoLiteTest
     */
    public function getTestCase()
    {
        return $this->testCase;
    }

    /**
     * Returns a single DOM element contained in the root context of
     * this page object based on a CSS selector
     *
     * @param string $cssSelector The CSS selector to be searched
     *
     * @return unknown A reference to a DOM element present in the page
     */
    public function byCssSelector($cssSelector)
    {
        return $this->rootContext->byCssSelector($cssSelector);
    }

    /**
     * Returns an array of multiple DOM elements contained in the
     * root context of this page object based on a CSS selector
     *
     * @param string $cssSelector The CSS selector to be searched
     * @return array An array of references to the DOM elements that
     * match the specified $cssSelector
     */
    public function byCssSelectorMultiple($cssSelector)
    {
        return $this->rootContext->elements($this->rootContext->using('css selector')->value($cssSelector));
    }

    /**
     * Returns an array of multiple DOM elements contained in the
     * root context of this page object based on an XPath expression
     *
     * @param string $xpath The xpath expression to be searched
     * @return array An array of references to the DOM elements that
     * match the specified $xpath
     */
    public function byXPathMultiple($xpath)
    {
        return $this->rootContext->elements($this->rootContext->using('xpath')->value($xpath));
    }

    /**
     * Returns the value of an attribute of the DOM element to which this
     * GenericPage instance corresponds
     *
     * @param string The name of the attribute
     * @returns string The value of the attribute
     */
    public function attribute($attrName)
    {
        return $this->rootContext->attribute($attrName);
    }

    /**
     * Types a string in the current browser window, just as if the user
     * was typing on the keyboard.
     *
     * @param string $string The string to be typed
     */
    public function type($string)
    {
        $this->testCase->keys($string);
    }

    /**
     * Hits the ENTER key in the current browser window just as if the user
     * typed that on the keyboard.
     */
    public function typeEnter()
    {
        $this->testCase->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);
    }

    /**
     * Hits the BACKSPACE key in the current browser window just as if the user
     * dit that on the keyboard.
     */
    public function typeBackspace()
    {
        $this->testCase->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::BACKSPACE);
    }

    /*
     * Hits the CTRL-HOME keys in the current browser window just as if the user
     * had done that in the browser. This is useful to return the cursor in the
     * beginning of a field
     */
    public function typeCtrlHome()
    {
        $this->testCase->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::CONTROL);
        $this->testCase->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::HOME);
        $this->testCase->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::CONTROL);
    }

    /**
     * Checks if a specific element is present within the root context
     * of the page object
     *
     * @param string $cssSelector The css selector to be searched
     * @return boolean True if the element is present, false otherwise
     */
    public function isElementPresent($cssSelector)
    {
        $this->testCase->timeouts()->implicitWait(1000);
        //temporarily decrease wait time so we don't have to wait too long
        //to find out that the element is really not present

        $isPresent =  count($this->byCssSelectorMultiple($cssSelector)) > 0;

        //restore original wait time
        $this->testCase->timeouts()->implicitWait(ExpressoLiteTest::DEFAULT_WAIT_INTERVAL);

        return $isPresent;
    }

    /**
     * This will make the test wait for any pending ajax calls, animations or throbbers.
     * It is just a shortcut for $this->testCase->waitForAjaxAndAnimations
     */
    public function waitForAjaxAndAnimations()
    {
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the element
     */
    public function click()
    {
        $this->rootContext->click();
    }
}
