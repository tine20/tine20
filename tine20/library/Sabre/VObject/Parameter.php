<?php

/**
 * VObject Parameter
 *
 * This class represents a parameter. A parameter is always tied to a property.
 * In the case of:
 *   DTSTART;VALUE=DATE:20101108 
 * VALUE=DATE would be the parameter name and value.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Parameter extends Sabre_VObject_Node {

    /**
     * Parameter name 
     * 
     * @var string 
     */
    public $name;

    /**
     * Parameter value 
     * 
     * @var string 
     */
    public $value;

    /**
     * Sets up the object 
     * 
     * @param string $name 
     * @param string $value 
     */
    public function __construct($name, $value = null) {

        $this->name = strtoupper($name);
        $this->value = $value;

    } 

    /**
     * Turns the object back into a serialized blob. 
     * 
     * VCalendar VERSION 2.0 parameters
     * Property parameters with values containing a COLON character, a
     * SEMICOLON character or a COMMA character MUST be placed in quoted
     * text.
     * 
     * VCard VERSION 3.0 parameter
     * RFC 2425 5.8.2.  ABNF content-type definition
     *  param-value  = ptext / quoted-string
     *  Any character except CTLs, DQUOTE, ";", ":", "," OR
     *  " Any character except CTLs, DQUOTE "
     *  
     * @return string 
     */
    public function serialize() {

        // esacpe ctl characters and remove double quotes
        $src = array(
            '\\',
            "\n",
            '"'
        );
        $out = array(
            '\\\\',
            '\n',
            ''
        );

        $value = str_replace($src, $out, $this->value);
        
        // doublequote value if it contains , ; or :
        if (preg_match('/[,:;]/', $this->value)) {
            $value = '"' . $value . '"';
        }
        
        return $this->name . '=' . $value;

    }

    /**
     * Called when this object is being cast to a string 
     * 
     * @return string 
     */
    public function __toString() {

        return $this->value;

    }

}
