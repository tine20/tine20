<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012 Kolab Systems AG (http://kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync Search Store request
 *
 * @package     Model
 * @property    string  name
 * @property    array   options
 * @property    array   query
 */
class Syncroton_Model_StoreRequest
{
    protected $_store = array();

    protected $_xmlStore;

    public function __construct($properties = null)
    {
        if ($properties instanceof SimpleXMLElement) {
            $this->setFromSimpleXMLElement($properties);
        } elseif (is_array($properties)) {
            $this->setFromArray($properties);
        }
    }

    public function setFromArray(array $properties)
    {
        $this->_store = array(
            'options' => array(
                'mimeSupport'     => Syncroton_Command_Sync::MIMESUPPORT_DONT_SEND_MIME,
                'bodyPreferences' => array()
            ),
        );

        foreach ($properties as $key => $value) {
            try {
                $this->$key = $value; //echo __LINE__ . PHP_EOL;
            } catch (InvalidArgumentException $iae) {
                //ignore invalid properties
                //echo __LINE__ . PHP_EOL;
            }
        }
    }

    /**
     *
     * @param SimpleXMLElement $xmlStore
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $xmlStore)
    {
        if ($xmlStore->getName() !== 'Store') {
            throw new InvalidArgumentException('Unexpected element name: ' . $xmlStore->getName());
        }

        $this->_xmlStore = $xmlStore;

        $this->_store = array(
            'name'             => (string) $xmlStore->Name,
            'options'          => array(
                'mimeSupport'     => Syncroton_Command_Sync::MIMESUPPORT_DONT_SEND_MIME,
                'bodyPreferences' => array(),
            ),
        );

        // Process Query
        if ($this->_store['name'] == 'GAL') {
            // @FIXME: In GAL search request Query is a string:
            // <Store><Name>GAL</Name><Query>string</Query><Options><Range>0-11</Range></Options></Store>
            if (isset($xmlStore->Query)) {
                $this->_store['query'] = (string) $xmlStore->Query;
            }
        } elseif (isset($xmlStore->Query)) {
            if (isset($xmlStore->Query->And)) {
                if (isset($xmlStore->Query->And->FreeText)) {
                    $this->_store['query']['and']['freeText'] = (string) $xmlStore->Query->And->FreeText;
                }
                if (isset($xmlStore->Query->And->ConversationId)) {
                    $this->_store['query']['and']['conversationId'] = (string) $xmlStore->Query->And->ConversationId;
                }

                // Protocol specification defines Value as string and DateReceived as datetime, but
                // PocketPC device I tested sends XML as follows:
                // <GreaterThan>
                //    <DateReceived>
                //    <Value>2012-08-02T16:54:11.000Z</Value>
                // </GreaterThan>

                if (isset($xmlStore->Query->And->GreaterThan)) {
                    if (isset($xmlStore->Query->And->GreaterThan->Value)) {
                        $value = (string) $xmlStore->Query->And->GreaterThan->Value;
                        $this->_store['query']['and']['greaterThan']['value'] = new DateTime($value, new DateTimeZone('UTC'));
                    }

                    $email = $xmlStore->Query->And->GreaterThan->children('uri:Email');
                    if (isset($email->DateReceived)) {
                        $this->_store['query']['and']['greaterThan']['dateReceived'] = true;
                    }
                }
                if (isset($xmlStore->Query->And->LessThan)) {
                    if (isset($xmlStore->Query->And->LessThan->Value)) {
                        $value = (string) $xmlStore->Query->And->LessThan->Value;
                        $this->_store['query']['and']['lessThan']['value'] = new DateTime($value, new DateTimeZone('UTC'));
                    }

                    $email = $xmlStore->Query->And->LessThan->children('uri:Email');
                    if (isset($email->DateReceived)) {
                        $this->_store['query']['and']['leasThan']['dateReceived'] = true;
                    }
                }

                $airSync = $xmlStore->Query->And->children('uri:AirSync');

                foreach ($airSync as $name => $value) {
                    if ($name == 'Class') {
                        $this->_store['query']['and']['classes'][] = (string) $value;
                    } elseif ($name == 'CollectionId') {
                        $this->_store['query']['and']['collections'][] = (string) $value;
                    }
                }
            }

            if (isset($xmlStore->Query->EqualTo)) {
                if (isset($xmlStore->Query->EqualTo->Value)) {
                    $this->_store['query']['equalTo']['value'] = (string) $xmlStore->Query->EqualTo->Value;
                }

                $doclib = $xmlStore->Query->EqualTo->children('uri:DocumentLibrary');
                if (isset($doclib->LinkId)) {
                    $this->_store['query']['equalTo']['linkId'] = (string) $doclib->LinkId;
                }
            }
        }

        // Process options
        if (isset($xmlStore->Options)) {
            // optional parameters
            if (isset($xmlStore->Options->DeepTraversal)) {
                $this->_store['options']['deepTraversal'] = true;
            }

            if (isset($xmlStore->Options->RebuildResults)) {
                $this->_store['options']['rebuildResults'] = true;
            }

            if (isset($xmlStore->Options->UserName)) {
                $this->_store['options']['userName'] = (string) $xmlStore->Options->UserName;
            }

            if (isset($xmlStore->Options->Password)) {
                $this->_store['options']['password'] = (string) $xmlStore->Options->Password;
            }

            if (isset($xmlStore->Options->Picture)) {
                if (isset($xmlStore->Options->Picture->MaxSize)) {
                    $this->_store['options']['picture']['maxSize'] = (int) $xmlStore->Options->Picture->MaxSize;
                }
                if (isset($xmlStore->Options->Picture->MaxPictures)) {
                    $this->_store['options']['picture']['maxPictures'] = (int) $xmlStore->Options->Picture->MaxPictures;
                }
            }

            if (!empty($xmlStore->Options->Range)) {
                $this->_store['options']['range'] = (string) $xmlStore->Options->Range;
            } else {
                switch ($this->_store['name']) {
                case 'DocumentLibrary':
                case 'Document Library': //?
                    '0-999';
                    break;
                case 'Mailbox':
                case 'GAL':
                default:
                    '0-99';
                    break;
                }
            }

            $this->_store['options']['range'] = explode('-', $this->_store['options']['range']);

            if (isset($xmlStore->Options->MIMESupport)) {
                $this->_store['options']['mimeSupport'] = (int) $xmlStore->Options->MIMESupport;
            }
/*
            if (isset($xmlStore->Options->MIMETruncation)) {
                $this->_store['options']['mimeTruncation'] = (int)$xmlStore->Options->MIMETruncation;
            }
*/
            // try to fetch element from AirSyncBase:BodyPreference
            $airSyncBase = $xmlStore->Options->children('uri:AirSyncBase');

            if (isset($airSyncBase->BodyPreference)) {
                foreach ($airSyncBase->BodyPreference as $bodyPreference) {
                    $type = (int) $bodyPreference->Type;
                    $this->_store['options']['bodyPreferences'][$type] = array(
                        'type' => $type
                    );

                    // optional
                    if (isset($bodyPreference->TruncationSize)) {
                        $this->_store['options']['bodyPreferences'][$type]['truncationSize'] = (int) $bodyPreference->TruncationSize;
                    }
                }
            }

            if (isset($airSyncBase->BodyPartPreference)) {
                // process BodyPartPreference elements
            }
        }
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this->_store)) {
            return $this->_store[$name];
        }
        //echo $name . PHP_EOL;
        return null;
    }

    public function __set($name, $value)
    {
        $this->_store[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_store[$name]);
    }

    public function __unset($name)
    {
        unset($this->_store[$name]);
    }
}