<?php

/**
 * Recure value property 
 *
 * This property is used for iCalendar properties such as the RRULE property. 
 * It basically provides a few helper functions that make it easier to deal 
 * with these.
 *
 * @package    Sabre
 * @subpackage VObject
 * @copyright  Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Property_Recure extends Sabre_VObject_Property {    
    
    /**
     * strip of common slashes from escaped values
     *  \\ => \
     *  \n => linebreak
     *  \: => :
     *  comma and semicolon are not handled here
     *
     * @param string $value
     * @return string
     */
    public static function stripSlashes($value) {
        $search  = array(
                '/\\\\/',
                #'/\\\,/',
                #'/\\\;/',
            	'/\\\n/'
        );
        $replace = array(
                '\\',
                #',',
                #';',
            	"\n"
        );
    
        return preg_replace($search, $replace, $value);
    }
    
    /**
     * add common slashes from values which must be escaped
     *
     * VCalendar VERSION 2.0 value
     * ESCAPED-CHAR = ("\\" / "\;" / "\," / "\N" / "\n") in TEXT values
     *
     * VCard VERSION 3.0 value
     * Backslashes, newlines, and commas must be encoded in values
     *
     * @param string $value
     * @return string
     */
    public function addSlashes($value) {
        $search  = array(
                '/\\\(?!,|;)/',
                "/\n/",
                #'/,/',
                #'/;/'
        );
        $replace = array(
                '\\\\\\',
                '\n',
                #'\,',
                #'\;'
        );
    
        return preg_replace($search, $replace, $value);
    }
    
}

