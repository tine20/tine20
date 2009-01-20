<?php
// @todo move that to /config dir
// rename to config.inc.php (and add update script for that)
// minimal configuration
return array(	
    'start_date' => array(
        'header'    => $this->_translate->_('Date'),
        'type'      => 'date', 
        'width'     => '2,5cm'
    ),
    'description' => array(
        'header'    => $this->_translate->_('Description'),
        'type'      => 'string', 
        'width'     => '10cm'
    ),
    'timeaccount_id' => array(
        'header'    => $this->_translate->_('Site'),
        'type'      => 'timeaccount', 
        'field'     => 'title', 
        'width'     => '7cm'
    ),
    'account_id' => array(
        'header'    => $this->_translate->_('Staff Member'),
        'type'      => 'account', 
        'field'     => 'accountDisplayName', 
        'width'     => '4cm'
    ),
    'duration' => array(
        'header'    => $this->_translate->_('Duration'),
        'type'      => 'float', 
        'width'     => '2cm',
        'divisor'   => 60 
    ),
    'is_billable' => array(
        'header'    => $this->_translate->_('Billable'),
        'type'      => 'float', 
        'width'     => '3cm'
    ),
    'is_cleared' => array(
        'header'    => $this->_translate->_('Cleared'),
        'type'      => 'float', 
        'width'     => '3cm'
    ),
    // custom fields follow
    /*
    'asp' =>  array(
        'header'    => $this->_translate->_('Ansprechpartner'),
        'type'      => 'string', 
        'width'     => '7cm',
        'custom'    => TRUE
    ),
    'newsletter' =>  array(
        'header'    => $this->_translate->_('NL'),
        'type'      => 'boolean',
        'values'    => array (0 => '', 1 => 'X'),
        'width'     => '2cm',
        'custom'    => TRUE
    ),
    */
);

