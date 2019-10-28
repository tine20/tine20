<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @todo invent common bootstrap for ServerTests and normal Tests to avoid code duplication
 */

/**
 * helper class
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
        $this->setWhiteAndBlacklists();

        $config = $this->getConfig();

        // set some server vars. sabredav complains if REQUEST_URI is not set
        $_SERVER['DOCUMENT_ROOT'] = $config->docroot;
        $_SERVER['REQUEST_URI'] = '';

        Tinebase_Core::startCoreSession();
        
        Tinebase_Core::initFramework();

        // set default test mailer
        Tinebase_Smtp::setDefaultTransport(new Zend_Mail_Transport_Array());

        // set max execution time
        Tinebase_Core::setExecutionLifeTime(1200);

        if ($config->locale) {
            Tinebase_Core::setupUserLocale($config->locale);
        }
        
        // this is needed for session handling in unittests (deactivate Zend_Session::writeClose and others)
        Zend_Session::$_unitTestEnabled = TRUE;

        Tinebase_Core::set('frameworkInitialized', true);
    }
    
    /**
     * Set white / black lists
     */
    public function setWhiteAndBlacklists()
    {
        if ($this->isPhpunitVersionGreaterOrEquals("4.0.0")) {
            // TODO make this work for newer phpunit versions
        } else if ($this->isPhpunitVersionGreaterOrEquals("3.6.0")) {
            // TODO not sure if this is working - we need to validate that
            $filter = new PHP_CodeCoverage_Filter();
            $filter->addDirectoryToBlacklist(PATH_TO_TEST_DIR);
            $filter->addDirectoryToBlacklist(PATH_TO_TINE_LIBRARY);
            $filter->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Setup');
            $filter->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Zend');
            $filter->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/vendor');
        } else if ($this->isPhpunitVersionGreaterOrEquals("3.5.0")) {
            PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_TEST_DIR);
            PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_TINE_LIBRARY);
            PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Setup');
            PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Zend');
        } else {
            PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TEST_DIR);
            PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TINE_LIBRARY);
            PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Setup');
            PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Zend');
        }
    }

    /**
     * inits (adds) some test users
     */
    public function initTestUsers()
    {
        $personas = $this->_getPersonas();
        if (count($personas) !== 5) {
            Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);
            Admin_Setup_DemoData::getInstance()->createDemoData(array('en'));
            Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
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
        if ($this->getConfig()->email) {
            // set email of test user contact
            $testUserContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
            $testUserContact->email = $this->getConfig()->email;
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
        if (! Zend_Registry::isRegistered('testConfig')) {
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
        } else {
            $config = Zend_Registry::get('testConfig');
        }

        return $config;
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
 
        $cmd = implode(' ', $_SERVER['argv']);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Original commmand: ' . $cmd);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Script name: ' . $_SERVER['SCRIPT_NAME']);
        
        $cmd = preg_replace(array(
            '@' . preg_quote($_SERVER['SCRIPT_NAME'], '@') . '@',
            '/--stderr /',
            '/--colors{0,1} /',
            '/--verbose /',
            '/--stop-on-failure /',
            '/[\S]+\.php$/',
            '/ \S+Tests{0,1}/',
            '/--debug\s*/',
            '/--filter [\S]+\D/',
            '/--configuration [\S]+\D/',
            '/--exclude-group [\S]+\D/',
            '/--coverage-[\S]+ [\S]+\D/',
            '/-c [\S]+\D/',
            '/--log-junit [\S]+\D/'
        ), array(
            'php',
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
            '',
        ), $cmd);
        
        $cmd .= $command;
        
        if ($addCredentials) {
            $credentials = TestServer::getInstance()->getTestCredentials();
            $cmd .= " --username {$credentials['username']} --password {$credentials['password']}";
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
        $maildomain = self::getPrimaryMailDomain();
        $tempPath = Tinebase_TempFile::getTempPath();
        $contents = file_get_contents($filename);
        $contents = preg_replace('/tine20.org/', $maildomain, $contents);
        file_put_contents($tempPath, $contents);
        
        return $tempPath;
    }

    /**
     * returns configured primary mail domain
     *
     * phpunit.config.inc > smtp config primary domain > current user mail domain > tine20.org
     *
     * @return mixed|string
     */
    public static function getPrimaryMailDomain()
    {
        $config = TestServer::getInstance()->getConfig();
        if ($config->maildomain) {
            return $config->maildomain;
        } else {
            $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
            if (isset($smtpConfig['primarydomain'])) {
                return $smtpConfig['primarydomain'];
            }

            if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
                list($user, $domain) = explode('@', Tinebase_Core::getUser()->accountEmailAddress, 2);
                return $domain;
            }

        }

        return 'tine20.org';
    }

    /**
     * isPhpunitVersionGreaterOrEquals
     * 
     * @param String $version for example '3.6.0'
     */
    public function isPhpunitVersionGreaterOrEquals($version)
    {
        $phpUnitVersion = explode(' ',PHPUnit_Runner_Version::getVersionString());
        return (version_compare($phpUnitVersion[1], $version) >= 0);
    }

    /**
     * login user
     *
     * @throws Exception
     */
    public function login()
    {
        $tinebaseController = Tinebase_Controller::getInstance();
        $credentials = $this->getTestCredentials();
        
        $config = $this->getConfig();
        $_SERVER['REMOTE_ADDR']     = $config->ip ? $config->ip : '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Unit Test Client';
        if (! $tinebaseController->login($credentials['username'], $credentials['password'], Tinebase_Core::getRequest(), 'TineUnittest')){
            throw new Exception("Couldn't login, user session required for tests! \n");
        }
    }
    
    /**
     * fetch test user credentials
     * 
     * @return array
     * 
     * @todo DRY: should be moved to abstract TestCase and used in ServerTestCase
     */
    public function getTestCredentials()
    {
        $config = $this->getConfig();
        $username = isset($config->login->username) ? $config->login->username : $config->username;
        $password = isset($config->login->password) ? $config->login->password : $config->password;
        
        return array(
            'username' => $username,
            'password' => $password
        );
    }
}
