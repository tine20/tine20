<?php
/**
 * Expresso Lite
 * Checks the list of events in the month or in the week
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Calendar;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Generic\DateUtils;
use ExpressoLiteTest\Functional\Mail\MailPage;
use ExpressoLiteTest\Functional\Login\LoginPage;
use ExpressoLiteTest\Functional\Addressbook\AddressbookPage;

class ListEventTest extends ExpressoLiteTest
{
    /*
     * Check if access in the other modules are possible
     *
     * CTV3-1117
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1117;
     */
    public function test_CTV3_1117_Access_other_modules()
    {
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->clickCalendar();

        $calendar = new CalendarPage($this);

        $this->assertTrue($calendar->hasCalendarScreenListed(), 'The calendar screen is visible, but it is not');
        $calendar->clickEmail();

        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $this->assertTrue($addressbookPage->hasAddressbookScreenListed(), 'The Addressbook screen is visible, but it is not');
        $calendar->clickEmail();

        $this->assertTrue($mailPage->hasEmailScreenListed(), 'The Email screen is visible, but it is not');

        $mailPage->clickLogout();
    }

    /*
     * Check check the schedules of view in a particular month
     *
     * CTV3-986
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-986;
     */
    public function test_CTV3_986_Schedules_Views_Month()
    {
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->clickCalendar();

        $calendar = new CalendarPage($this);

        $this->assertEquals($calendar->getMonthAndYearInLayout_Title(), DateUtils::getMonthYear(),
                "Month and year does not match in Layout Title of Calendar Page");

        $this->assertEquals($calendar->getDayToday(), DateUtils::getday(),
                "The today does not match in today's day of Calendar of month");

        $this->assertEquals($calendar->getWeekPositionOfCurrentDay(), DateUtils::getNumericDayOfWeek(),
                "The Numeric representation of the day of week does not match in the day of week on Calendar Page");

        $calendar->clickLastMonthButton();

        $this->assertEquals($calendar->getMonthAndYearInLayoutTitle(), DateUtils::getPreviousMonthYear(),
                "Previous month and current year does not match in Layout Title of Calendar Page");

        $calendar->clickNextMonthButton();
        $calendar->clickNextMonthButton();

        $this->assertEquals($calendar->getMonthAndYearInLayout_Title(), DateUtils::getNextMonthYear(),
                "Next month and current year does not match in Layout Title of Calendar Page");
    }

       /*
     * Check check the schedules of view in a particular week
     *
     * CTV3-987
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-987;
     */
    public function test_CTV3_987_Schedules_Views_Week()
    {
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->clickCalendar();

        $calendar = new CalendarPage($this);
        $calendar->clickViewWeek();
        $this->waitForAjaxAndAnimations();

        $this->assertContains(DateUtils::getNumericDayMonth(), $calendar->getDayWeekToday(),
                "The today does not match in day and month of Calendar of week");

        $this->assertContains(DateUtils::getShortDayOfWeek(), $calendar->getDayWeekToday(),
                "The today does not match in today's day of Calendar of week");

        //Verify if has the format date in layout title
        $this->assertTrue(
                preg_match_all(
                    "/\A\d{1,2}( [a-z]{3})? - \d{1,2} [a-z]{3,}, \d{4}\z/",
                    $calendar->getMonthAndYearInLayoutTitle(), $matches) == 1,
                "No match date of week in layout title  on Calendar of week");

        $calendar->clickLastWeekButton();

        $this->assertTrue(
                preg_match_all(
                    "/\A\d{1,2}( [a-z]{3})? - \d{1,2} [a-z]{3,}, \d{4}\z/",
                    $calendar->getMonthAndYearInLayoutTitle(), $matches) == 1,
                "No match date of last week in layout title on Calendar of week");

        $calendar->clickNextWeekButton();
        $calendar->clickNextWeekButton();

        $this->assertTrue(
                preg_match_all(
                    "/\A\d{1,2}( [a-z]{3})? - \d{1,2} [a-z]{3,}, \d{4}\z/",
                    $calendar->getMonthAndYearInLayoutTitle(), $matches) == 1,
                "No match date of Next week in layout title  on Calendar of week");
    }
}