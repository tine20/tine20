<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     Tinebase
 * @subpackage  BL
 */
interface Tinebase_BL_ElementConfigInterface
{
    /**
     * this method needs to return a new BLElement, only recycle objects if you are absolutely sure of the stateless
     * nature of your object
     *
     * @return Tinebase_BL_ElementInterface
     */
    public function getNewBLElement();

    /**
     * The comparison function must return an integer less than, equal to, or
     * greater than zero if this is considered to be
     * respectively less than, equal to, or greater than the argument.
     */
    public function cmp(Tinebase_BL_ElementConfigInterface $_element);
}