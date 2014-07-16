<?php
class Tinebase_Model_Filter_DateMock extends Tinebase_Model_Filter_Date {
    
    /**
     * may hold a date self::now() will return
     * 
     * @var Tinebase_DateTime
     */
    public static $testDate = NULL;
    
    /**
     * returns the current date if no $date string is given (needed for mocking only)
     *
     * @param string $date
     * @param boolean $usertimezone
     */
    protected function _getDate($date = NULL, $usertimezone = FALSE)
    {
        if (self::$testDate == NULL) {
            return parent::_getDate($date, $usertimezone);
        }
        
        return self::$testDate;
    }

    /**
     * Returns the actual date as new date object
     *
     * @return Tinebase_DateTime
     */
    public static function now()
    {
        if (self::$testDate === NULL) {
            return new Tinebase_DateTime();
        }
        return self::$testDate;
    }
}