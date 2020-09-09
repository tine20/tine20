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
 * Tinebase BusinessLogic PipeContext:
 *
 * * it plays the roll of a container service and event broker (to be implemented)
 *
 * @package     Tinebase
 * @subpackage  BL
 */
interface Tinebase_BL_PipeContext
{
    /**
     * @return int
     */
    public function getCurrentExecutionOffset();

    /**
     * @param string $_class
     * @param int $_before
     * @return null|Tinebase_BL_ElementInterface
     */
    public function getLastElementOfClassBefore($_class, $_before);
}