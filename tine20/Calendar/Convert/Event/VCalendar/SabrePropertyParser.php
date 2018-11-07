<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Test class for Calendar_Frontend_iMIP
 */
class Calendar_Convert_Event_VCalendar_SabrePropertyParser extends \Sabre\VObject\Parser\MimeDir
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(\Sabre\VObject\Component $root)
    {
        $this->root = $root;
    }

    /**
     * @param string $data
     * @return \Sabre\VObject\Property
     * @throws \Sabre\VObject\ParseException
     */
    public function parseProperty($data)
    {
        /** stupid Sabre, their method signature markup is broken! */
        /** @var \Sabre\VObject\Property $property */
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $property = $this->readProperty($data);
        return $property;
    }
}