<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Class to handle ActiveSync Search/Response/Store element
 *
 * @package    Syncroton
 * @subpackage Model
 * @property  string  status
 * @property  array   result
 * @property  array   range
 * @property  int     total
 */
class Syncroton_Model_StoreResponse extends Syncroton_Model_AEntry
{
    /**
     * status constants
     */
    const STATUS_SUCCESS            = 1;
    const STATUS_INVALIDREQUEST     = 2;
    const STATUS_SERVERERROR        = 3;
    const STATUS_BADLINK            = 4;
    const STATUS_ACCESSDENIED       = 5;
    const STATUS_NOTFOUND           = 6;
    const STATUS_CONNECTIONFAILED   = 7;
    const STATUS_TOOCOMPLEX         = 8;
    const STATUS_TIMEDOUT           = 10;
    const STATUS_FOLDERSYNCREQUIRED = 11;
    const STATUS_ENDOFRANGE         = 12;
    const STATUS_ACCESSBLOCKED      = 13;
    const STATUS_CREDENTIALSREQUIRED = 14;

    protected $_xmlBaseElement = 'Store';

    protected $_properties = array(
        'Search' => array(
            'status'    => array('type' => 'number'),
            'result'    => array('type' => 'container', 'multiple' => true),
            'range'     => array('type' => 'string'),
            'total'     => array('type' => 'number'),
        )
    );

    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_AEntry::appendXML()
     */
    public function appendXML(DOMElement $_domParrent, Syncroton_Model_IDevice $device)
    {
        $this->_addXMLNamespaces($_domParrent);

        foreach ($this->_elements as $elementName => $value) {
            // skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);

            $nameSpace = 'uri:' . $nameSpace;

            switch ($elementName) {
                case 'result':
                    foreach ($value as $result) {
                        $element = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Result');
                        $result->appendXML($element, $device);
                        $_domParrent->appendChild($element);
                    }
                    break;

                case 'range':
                    if (is_array($value) && count($value) == 2) {
                        $value = implode('-', $value);
                    }

                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, ucfirst($elementName));
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
                    $_domParrent->appendChild($element);
            }
        }
    }
}
