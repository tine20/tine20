<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * SetupRequired exception
 * 
 * @package     Setup
 * @subpackage  Exception
 */
class Setup_Exception_PromptUser extends Setup_Exception
{
    public function __construct($_message, $_code) {
        parent::__construct('This update could be run from cli only!', 901);
    }
}
