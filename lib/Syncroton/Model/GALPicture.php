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
 * class to handle ActiveSync GAL Picture element
 *
 * @package     Syncroton
 * @subpackage  Model
 *
 * @property    string    Status
 * @property    string    Data
 */
class Syncroton_Model_GALPicture extends Syncroton_Model_AEntry
{
    const STATUS_SUCCESS   = 1;
    const STATUS_NOPHOTO   = 173;
    const STATUS_TOOLARGE  = 174;
    const STATUS_OVERLIMIT = 175;

    protected $_xmlBaseElement = 'ApplicationData';

    protected $_properties = array(
        'AirSync' => array(
            'Status'       => array('type' => 'number'),
        ),
        'GAL' => array(
            'Data'         => array('type' => 'byteArray'),
        ),
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
