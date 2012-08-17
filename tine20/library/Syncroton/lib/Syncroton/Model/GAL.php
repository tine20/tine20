<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync GAL result
 *
 * @package     Syncroton
 * @subpackage  Model
 *
 * @property    string    Alias
 * @property    string    Company
 * @property    string    DisplayName
 * @property    string    EmailAddress
 * @property    string    FirstName
 * @property    string    LastName
 * @property    string    MobilePhone
 * @property    string    Office
 * @property    string    Phone
 * @property    string    Picture
 * @property    string    Title
 */
class Syncroton_Model_GAL extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';

    protected $_properties = array(
        'GAL' => array(
            'Alias'         => array('type' => 'string'),
            'Company'       => array('type' => 'string'),
            'DisplayName'   => array('type' => 'string'),
            'EmailAddress'  => array('type' => 'string'),
            'FirstName'     => array('type' => 'string'),
            'LastName'      => array('type' => 'string'),
            'MobilePhone'   => array('type' => 'string'),
            'Office'        => array('type' => 'string'),
            'Phone'         => array('type' => 'string'),
            'Picture'       => array('type' => 'composite'),
            'Title'         => array('type' => 'string'),
        )
    );

    public function appendXML(DOMElement $_domParrent)
    {
        $this->_addXMLNamespaces($_domParrent);

        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);

            $nameSpace = 'uri:' . $nameSpace;

            // strip off any non printable control characters
/*
            if (!ctype_print($value)) {
                $value = $this->removeControlChars($value);
            }
*/
            switch ($elementName) {
                case 'Picture':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    $value->appendXML($element);
                    $_domParrent->appendChild($element);
                    break;

                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);

                    if (isset($elementProperties['encoding']) && $elementProperties['encoding'] == 'base64') {
                        if (is_resource($value)) {
                            stream_filter_append($value, 'convert.base64-encode');
                            $value = stream_get_contents($value);
                        } else {
                            $value = base64_encode($value);
                        }
                    }

                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));

                    $_domParrent->appendChild($element);
            }
        }
    }
}
