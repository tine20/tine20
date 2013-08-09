<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * helper class
 *
 */
class TestServer
{
    /**
     * holdes the instance of the singleton
     *
     * @var TestServer
     */
    private static $instance = NULL;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * the singleton pattern
     *
     * @return TestServer
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new TestServer;
        }

        return self::$instance;
    }

    /**
     * init the test framework
     */
    public function initFramework()
    {
        // get config
        $configData = @include('phpunitconfig.inc.php');
        if ($configData === false) {
            $configData = include('config.inc.php');
        }
        if ($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $config = new Zend_Config($configData);

        Zend_Registry::set('testConfig', $config);

        $_SERVER['DOCUMENT_ROOT'] = $config->docroot;

        Tinebase_Core::initFramework();

        // set default test mailer
        Tinebase_Smtp::setDefaultTransport(new Zend_Mail_Transport_Array());

        // set max execution time
        Tinebase_Core::setExecutionLifeTime(1200);

        Zend_Registry::set('locale', new Zend_Locale($config->locale));
        
        // this is needed for session handling in unittests (deactivate Zend_Session::writeClose and others)
        Zend_Session::$_unitTestEnabled = TRUE;
    }

    /**
     * inits (adds) some test users
     */
    public function initTestUsers()
    {
        $personas = $this->_getPersonas();
        if (empty($personas)) {
            Admin_Setup_DemoData::getInstance()->createDemoData('en');
            $personas = $this->_getPersonas();
        }
        
        Zend_Registry::set('personas', $personas);
    }
    
    /**
     * fetch persona user accounts
     * 
     * @return array loginname => useraccount
     */
    protected function _getPersonas()
    {
        $personas = array();
        $personaLoginNames = array('sclever', 'rwright', 'pwulf', 'jmcblack', 'jsmith');
        foreach ($personaLoginNames as $loginName) {
            try {
                $personas[$loginName] = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        }
        return $personas;
    }

    /**
     * set test user email address if in config
     */
    public function setTestUserEmail()
    {
        if (Zend_Registry::get('testConfig')->email) {
            // set email of test user contact
            $testUserContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
            $testUserContact->email = Zend_Registry::get('testConfig')->email;
            Addressbook_Controller_Contact::getInstance()->update($testUserContact, FALSE);
        }
    }

    /**
     * get test config
     * 
     * @return Zend_Config
     */
    public function getConfig()
    {
        return Zend_Registry::get('testConfig');
    }
    
    /**
     * assemble CLI command line call (tine20.php)
     * 
     * @param string $command
     * @param bool   $addCredentials
     * @return string
     */
    public static function assembleCliCommand($command = "", $addCredentials = FALSE)
    {
        //$backtrace = debug_backtrace();
        //return $backtrace[1]['class'];
 
        // assemble command
        $cmd = implode(' ', $_SERVER['argv']);
        
        $cmd = preg_replace(array(
            '/\/phpunit/',
            '/--stderr /',
            '/--colors /',
            '/--verbose /',
            '/--stop-on-failure /',
            '/[\S]+\.php$/',
            '/ \S+Tests{0,1}/',
            '/--debug /',
            '/--filter [\S]+\D/',
            '/--configuration [\S]+\D/',
            '/-c [\S]+\D/',
            '/--log-junit [\S]+\D/'
        ), array(
            '/php',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ), $cmd);
        
        $cmd .= $command;
        
        if ($addCredentials) {
            $config = TestServer::getInstance()->getConfig();
            $cmd .= " --username {$config->username} --password {$config->password}";
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Assembled commmand: ' . $cmd);
        
        return $cmd;
    }

    /**
     * replace maildomain in input file
     * 
     * @param string $filename
     * @return string filename
     */
    public static function replaceEmailDomainInFile($filename)
    {
        $config = TestServer::getInstance()->getConfig();
        $maildomain = ($config->maildomain) ? $config->maildomain : 'tine20.org';
        $tempPath = Tinebase_TempFile::getTempPath();
        $contents = file_get_contents($filename);
        $contents = preg_replace('/tine20.org/', $maildomain, $contents);
        file_put_contents($tempPath, $contents);
        
        return $tempPath;
    }
}
