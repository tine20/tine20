<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Log_Formatter
 */
class Tinebase_Log_FormatterTest extends Tinebase_Log_Formatter_AbstractTest
{
    /**
     * test pw replacements
     */
    public function testPWReplacements()
    {
        $config = Tinebase_Core::getConfig();
        $password = '(pwd string)' . $config->database->password . '(pwd string)';
        
        $formatter = new Tinebase_Log_Formatter();
        $formatter->addReplacement($password);
        $this->_setWriterWithFormatter($formatter);
        
        $filter = new Zend_Log_Filter_Priority(5);
        $this->_logger->addFilter($filter);

        $this->_logger->notice($password);
        $loggerFile = file_get_contents($this->_logfile);

        if (strpos($loggerFile, Tinebase_User::SYSTEM_USER_SETUP) !== false) {
            $username = Tinebase_User::SYSTEM_USER_SETUP;
        } else {
            $username = Tinebase_Core::getUser()->accountLoginName;
        }

        $this->assertFalse(strpos($loggerFile, $password), 'pw found!');
        $this->assertContains('********', $loggerFile);
        if ($config->logger->logruntime || $config->logger->logdifftime) {
            $this->assertTrue(preg_match('/' . $username . ' \d/', $loggerFile) === 1);
        } else {
            $this->assertContains($username, $loggerFile);
        }
    }

    public function testColorize()
    {
        $formatter = new Tinebase_Log_Formatter();
        $formatter->setOptions(array('colorize' => true));
        $this->_setWriterWithFormatter($formatter);

        $filter = new Zend_Log_Filter_Priority(5);
        $this->_logger->addFilter($filter);

        $this->_logger->notice("test logging");
        $loggerFile = file_get_contents($this->_logfile);

        self::assertContains("\e[1m", $loggerFile, 'did not find ansi color escape code');
        self::assertContains("\e[36m", $loggerFile, 'did not find color for prio 5 (36m)');
    }
}
