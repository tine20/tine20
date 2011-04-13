<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 *
 */

/**
 * SetupRequired exception
 * 
 * @package     Setup
 * @subpackage  Exception
 */
class Setup_Exception_Dependency extends Setup_Exception
{
    public function __construct($_message, $_code=501) {
        parent::__construct($_message, $_code);
    }
}
