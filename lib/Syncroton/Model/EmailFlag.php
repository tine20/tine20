<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync Flag element
 *
 * @package     Model
 * @property    DateTime  CompleteTime
 * @property    DateTime  DateCompleted
 * @property    DateTime  DueDate
 * @property    string    FlagType
 * @property    DateTime  OrdinalDate
 * @property    int       ReminderSet
 * @property    DateTime  ReminderTime
 * @property    DateTime  StartDate
 * @property    string    Status
 * @property    string    Subject
 * @property    string    SubOrdinalDate
 * @property    DateTime  UtcDueDate
 * @property    DateTime  UtcStartDate
 */
class Syncroton_Model_EmailFlag extends Syncroton_Model_AEntry
{
    const STATUS_CLEARED  = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_ACTIVE   = 2;

    protected $_xmlBaseElement = 'Flag';

    protected $_properties = array(
        'Email' => array(
            'CompleteTime'       => array('type' => 'datetime'),
            'FlagType'           => array('type' => 'string'),
            'Status'             => array('type' => 'number'),
        ),
        'Tasks' => array(
            'DateCompleted'      => array('type' => 'datetime'),
            'DueDate'            => array('type' => 'datetime'),
            'OrdinalDate'        => array('type' => 'datetime'),
            'ReminderSet'        => array('type' => 'number'),
            'ReminderTime'       => array('type' => 'datetime'),
            'StartDate'          => array('type' => 'datetime'),
            'Subject'            => array('type' => 'string'),
            'SubOrdinalDate'     => array('type' => 'string'),
            'UtcStartDate'       => array('type' => 'datetime'),
            'UtcDueDate'         => array('type' => 'datetime'),
        ),
    );

    protected function _parseEmailNamespace(SimpleXMLElement $properties)
    {
        // fetch data from AirSyncBase namespace
        $children = $properties->children('uri:Email');

        foreach ($children as $elementName => $xmlElement) {
            switch ($elementName) {
                case 'FlagStatus':
                    // Android bug http://code.google.com/p/android/issues/detail?id=36113
                    $elementName = 'Status';

                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);

                    switch ($elementProperties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            break;

                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            break;

                        default:
                            $this->$elementName = (string) $xmlElement;
                            break;
                    }
            }
        }
    }

    protected function _parseTasksNamespace(SimpleXMLElement $properties)
    {
        // fetch data from AirSyncBase namespace
        $children = $properties->children('uri:Tasks');

        foreach ($children as $elementName => $xmlElement) {
            switch ($elementName) {
                default:
                    list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);

                    switch ($elementProperties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            break;

                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            break;

                        default:
                            $this->$elementName = (string) $xmlElement;
                            break;
                    }
            }
        }
    }
}
