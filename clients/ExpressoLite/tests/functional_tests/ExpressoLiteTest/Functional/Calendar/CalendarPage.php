<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite Calendar module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Calendar;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class CalendarPage extends GenericPage
{
    /**
     * Clicks on the Email link on the left of the screen
     * and waits for the Email window to be displayed
     */
    public function clickEmail()
    {
        $this->byCssSelector('.Layout_iconEmail')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the previous button on the top of Calendar Page
     * and waits for the last month to be displayed
     */
    public function clickLastMonthButton()
    {
        $this->byCssSelector('.Month_prev')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the previous button on the top of Calendar Page
     * and waits for the last week to be displayed
     */
    public function clickLastWeekButton()
    {
        $this->byCssSelector('.Week_prev')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the next button on the top of Calendar Page
     * and waits for the next month to be displayed
     */
    public function clickNextMonthButton()
    {
        $this->byCssSelector('.Month_next')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the next button on the top of Calendar Page
     * and waits for the next week to be displayed
     */
    public function clickNextWeekButton()
    {
        $this->byCssSelector('.Week_next')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }
    /**
     * Checks if the Calendar container was displayed
     *
     * @return boolean
     */
    public function hasCalendarScreenListed()
    {
        return $this->isElementPresent('.Month_container');
    }

    /**
     * Checks if the Calendar layout has month and year in the title
     *
     * @return text
     */
    public function getMonthAndYearInLayoutTitle()
    {
        return $this->byCssSelector('#Layout_title')->text();
    }

    /**
     * Returns the string of total entries in Catalog
     */
    public function getDayToday()
    {
        return $this->byCssSelector('.Month_dayToday > .Month_dayDisplay')->text();
    }

    /**
     * Returns the string of total entries in Catalog
     */
    public function getDayWeekToday()
    {
        return $this->byCssSelector('.Week_labelWeekToday')->text();
    }

    public function getWeekPositionOfCurrentDay()
    {
        $dayToday = $this->getDayToday();

        //this xpath expression searches for:
        // - //div[contains(@class, 'Month_dayToday')]: the current day
        // - /..: the parent of the current day
        // - /*; all the children of the parent of the current day (that is, current day siblings)
        $currentWeekDivs = $this->byXPathMultiple("//div[contains(@class, 'Month_dayToday')]/../*");

        for ($i=0; $i < count($currentWeekDivs); $i++) {
            $dayDiv = $currentWeekDivs[$i];
            if ($dayDiv->text() == $dayToday) {
                return $i;
            }
        }

        throw new \Exception('Could not find position of current day');
    }

    /**
     * Clicks on the choose view menu
     */
    public function clickViewWeek()
    {
        $this->clickOnMenuView('Ver semana');
    }

    /**
     * Clicks on a menu item within the context menu
     *
     * @param string $itemText The text of the item to be clicked
     */
    private function clickOnMenuView($itemText)
    {
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("Menu item with text $itemText was not found");
    }

    /**
     * Returns an array of <li> elements within the context menu
     *
     * @returns array Array of <li> elements within the context menu
     */
    private function getContextMenuItems()
    {
        return $this->byCssSelectorMultiple('#chooseViewMenu > .SimpleMenu_list span');
    }

}
