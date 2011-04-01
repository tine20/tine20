<?php
class qCal_Property_XLvFoo extends qCal_Property_MultiValue {

    protected $name = "X-LV-FOO";
    protected $type = "TEXT";
    protected $allowMultiple = true;
    protected $allowedComponents = array('VTODO','VJOURNAL');

}