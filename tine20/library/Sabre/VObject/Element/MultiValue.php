<?php

/**
 * MultiValue property 
 *
 * This element is used for iCalendar properties such as the DTSTART property. 
 * It basically provides a few helper functions that make it easier to deal 
 * with these. It supports both DATE-TIME and DATE values.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Element_MultiValue extends Sabre_VObject_Property {
    
    const DELIMITER = ';';
    
    /**
     * add common slashes from values which must be escaped
     *  \\ => \
     *  \n => linebreak
     *  \: => :
     *  comma and semicolon are not handled here
     *
     * @param string $value
     * @return string
     */
    public function addSlashes($value) {
        
        foreach ($value as &$_value) {
            $_value = parent::addSlashes($_value);
        }
        
        return $this->concatCompoundValues($value, self::DELIMITER);
    }
    
    /**
     * Updates the internal value
     *
     * @param string $value
     * @return void
     */
    public function setValue($value) {
    
        if (!is_array($value)) {
            $value = $this->splitCompoundValues($value, self::DELIMITER);
        }
        $this->value = $value;
    
    }
    
    /**
     * Called when this object is being cast to a string
     *
     * @return string
     */
    public function __toString() {
    
        return $this->concatCompoundValues($this->value, self::DELIMITER);
    
    }
    
}

