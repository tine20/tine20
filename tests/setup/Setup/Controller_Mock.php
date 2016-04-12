<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Test mock
 */

class Setup_Controller_Mock extends Setup_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getBackupStructureOnlyTables()
    {
        return $this->_getBackupStructureOnlyTables();
    }
}