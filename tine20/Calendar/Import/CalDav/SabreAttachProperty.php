<?php

class Calendar_Import_CalDav_SabreAttachProperty extends Sabre\VObject\Property\Binary
{
    protected $isValueBinary = true;
    
    public function setRawMimeDirValue($val)
    {
        if (($tmp = base64_encode($val)) == base64_decode($tmp))
        {
            $this->value = tmp;
        } else {
            $this->isValueBinary = false;
            $this->value = $val;
        }
    }
    
    public function getRawMimeDirValue()
    {
        if ($this->isValueBinary)
            return base64_encode($this->value);
        else
            return $this->value;
    }
}