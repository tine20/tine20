<?php
/**
 * Tine 2.0
 * 
 * @package     setup tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

Tinebase_Session_Abstract::setSessionEnabled('TINE20SETUPSESSID');

Setup_TestServer::getInstance()->initFramework();
