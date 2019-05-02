<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  RAII
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * finally as RAII destructor helper
 *
 * @package     Tinebase
 * @subpackage  RAII
 */
class Tinebase_RAII
{
    protected $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function __destruct()
    {
        ($this->closure)();
    }
}