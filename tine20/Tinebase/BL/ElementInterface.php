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
 * BL Element Interface ... these classes will be executed only once per BL Pipe run! They can thus dynamically adjust
 * their state and do not need to support a reset. If they depend on external objects, like a configuration object,
 * they should clone it before altering it.
 *
 * @package     Tinebase
 * @subpackage  BL
 */
interface Tinebase_BL_ElementInterface
{
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data);
}