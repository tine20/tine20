<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Setup json frontend
 *
 * @package     Setup
 * @subpackage  Frontend
 */
class Setup_Frontend_Json extends Tinebase_Frontend_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Setup';

    /**
     * setup controller
     *
     * @var Setup_Controller
     */
    protected $_controller = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_controller = Setup_Controller::getInstance();
    }
    
    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    public function login($username, $password)
    {
        Setup_Core::startSetupSession();
        
        if (Setup_Controller::getInstance()->login($username, $password)) {
            $response = array(
                'success'       => TRUE,
                //'account'       => Tinebase_Core::getUser()->getPublicUser()->toArray(),
                //'jsonKey'       => Setup_Core::get('jsonKey'),
                'welcomeMessage' => "Welcome to Tine 2.0 Setup!"
            );
        } else {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "Wrong username or password!"
            );
        }

        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        Setup_Controller::getInstance()->logout();

        return array(
            'success'=> true,
        );
    }
    
    /**
     * install new applications
     *
     * @param array $applicationNames application names to install
     * @param array | optional $options
     */
    public function installApplications($applicationNames, $options = null)
    {
        if (is_array($applicationNames)) {
            $this->_controller->installApplications($applicationNames, $options);
               
            $result = TRUE;
        } else {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' $applicationNames is not an array: ' . $applicationNames);
            $result = FALSE;
        }
        
        return array(
            'success' => $result,
            'setupRequired' => $this->_controller->setupRequired()
        );
    }

    /**
     * update existing applications
     *
     * @param array $applicationNames application names to update
     * @return array
     *
     * TODO remove $applicationNames param and adopt js client
     */
    public function updateApplications($applicationNames)
    {
        // always update all update-able applications
        $this->_controller->updateApplications();

        return array(
            'success'=> true,
            'setupRequired' => $this->_controller->setupRequired()
        );
    }

    /**
     * uninstall applications
     *
     * @param array $applicationNames application names to uninstall
     * @return array
     */
    public function uninstallApplications($applicationNames)
    {
        $this->_controller->uninstallApplications($applicationNames);
        
        return array(
            'success'=> true,
            'setupRequired' => $this->_controller->setupRequired()
        );
    }
    
    /**
     * search for installed and installable applications
     *
     * @return array
     */
    public function searchApplications()
    {
        return $this->_controller->searchApplications();
    }
    
    /**
     * do the environment check
     *
     * @return array
     */
    public function envCheck()
    {
        return Setup_Controller::getInstance()->checkRequirements();
    }

    /**
     * load config data from config file / default data
     *
     * @return array
     */
    public function loadConfig()
    {
        $result = (! Setup_Core::configFileExists()) 
                ? Setup_Controller::getInstance()->getConfigDefaults()
                : ((Setup_Core::isRegistered(Setup_Core::USER)) ? Setup_Controller::getInstance()->getConfigData() : array());

        return $result;
    }
    
    /**
     * save config data in config file
     *
     * @param  array $data
     * @return array with config data
     */
    public function saveConfig($data)
    {
        Setup_Controller::getInstance()->saveConfigData($data);
        
        return $this->checkConfig();
    }
    
    /**
     * check config and return status
     *
     * @return array
     * 
     * @todo add check if db settings have changed?
     */
    public function checkConfig()
    {
        Setup_Core::setupDatabaseConnection();
        $checkDB = Setup_Core::get(Setup_Core::CHECKDB);
        
        $result = array(
            'configExists'    => Setup_Core::configFileExists(),
            'configWritable'  => Setup_Core::configFileWritable(),
            'checkDB'         => $checkDB,
            'checkLogger'     => $this->_controller->checkConfigLogger(),
            'checkCaching'    => $this->_controller->checkConfigCaching(),
            'checkQueue'      => $this->_controller->checkConfigQueue(),
            'checkTmpDir'     => $this->_controller->checkDir('tmpdir',null, false),
            'checkSession'    => $this->_controller->checkConfigSession(),
            'checkFilesDir'   => $this->_controller->checkDir('filesdir', null, false),
            'setupRequired'      => empty($checkDB) ? TRUE : $this->_controller->setupRequired(),
        );

        return $result;
    }
    
    /**
     * load auth config data
     * 
     * @return array
     */
    public function loadAuthenticationData()
    {
        return $this->_controller->loadAuthenticationData();
    }
    
    /**
     * Update authentication data (needs Tinebase tables to store the data)
     * 
     * Installs Tinebase if not already installed
     * 
     * @todo validate $data
     * 
     * @param  array $data
     * @return array [success status]
     */
    public function saveAuthentication($data)
    {
        $this->_controller->saveAuthentication($data);
        return array(
            'success' => true,
            'setupRequired' => $this->_controller->setupRequired()
        );
    }
    
    /**
     * load email config data
     * 
     * @return array
     */
    public function getEmailConfig()
    {
        return $this->_controller->getEmailConfig();
    }
    
    /**
     * Update email config data
     * 
     * @param  array $data
     * @return array [success status]
     */
    public function saveEmailConfig($data)
    {
        $this->_controller->saveEmailConfig($data);
        return array(
            'success' => true,
        );
    }
    
    /**
     * Returns registry data of setup
     * .
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     * 
     * @todo add 'titlePostfix'    => Tinebase_Config::getInstance()->getConfig(Tinebase_Config::PAGETITLEPOSTFIX, NULL, '')->value here?
     */
    public function getRegistryData()
    {
        // anonymous registry
        $registryData =  array(
            'configExists'     => Setup_Core::configFileExists(),
            'version'          => array(
                'buildType'     => TINE20_BUILDTYPE,
                'codeName'      => TINE20SETUP_CODENAME,
                'packageString' => TINE20SETUP_PACKAGESTRING,
                'releaseTime'   => TINE20SETUP_RELEASETIME,
                // NOTE: if assetHash is not available we have a serious problem -  please don't generate one!
                'assetHash'     => Tinebase_Frontend_Http_SinglePageApplication::getAssetHash(),
            ),
            'authenticationData'   => $this->loadAuthenticationData(),
        );
        
        // authenticated or non existent config
        if (! Setup_Core::configFileExists() || Setup_Core::isRegistered(Setup_Core::USER)) {
            $registryData = array_merge($registryData, $this->checkConfig());
            $registryData = array_merge($registryData, array(
                'rights'               => ['admin'],
                'acceptedTermsVersion' => (! empty($registryData['checkDB']) && $this->_controller->isInstalled('Tinebase')) ? Setup_Controller::getInstance()->getAcceptedTerms() : 0,
                'setupChecks'          => $this->envCheck(),
                'configData'           => $this->loadConfig(),
                'emailData'            => (! empty($registryData['checkDB']) && $this->_controller->isInstalled('Tinebase')) ? $this->getEmailConfig() : array(),
            ));
        }
        
        // if setup user is logged in
        if (Setup_Core::isRegistered(Setup_Core::USER)) {
            $registryData += array(
                'currentAccount'   => Setup_Core::getUser(),
            );
        }
        
        return $registryData;
    }
    
    /**
     * Returns registry data of all applications current user has access to
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     *
     * TODO DRY: most of this already is part of Tinebase_Frontend_Json::_getAnonymousRegistryData
     */
    public function getAllRegistryData()
    {
        // clear cache to make sure we have current data...
        Setup_Controller::getInstance()->clearCache();

        $registryData['Setup'] = $this->getRegistryData();
        
        // setup also need some core tinebase regdata
        $locale = Tinebase_Core::get('locale');
        $symbols = Zend_Locale::getTranslationList('symbols', $locale);

        $registryData['Tinebase'] = array(
            'serviceMap'       => Setup_Frontend_Http::getServiceMap(),
            'timeZone'         => Setup_Core::getUserTimezone(),
            'jsonKey'          => Setup_Core::get('jsonKey'),
            'locale'           => array(
                'locale'   => $locale->toString(), 
                'language' => Zend_Locale::getTranslation($locale->getLanguage(), 'language', $locale),
                'region'   => Zend_Locale::getTranslation($locale->getRegion(), 'country', $locale),
            ),
            'version'          => array(
                'buildType'     => TINE20_BUILDTYPE,
                'codeName'      => TINE20SETUP_CODENAME,
                'packageString' => TINE20SETUP_PACKAGESTRING,
                'releaseTime'   => TINE20SETUP_RELEASETIME,
                // NOTE: if assetHash is not available we have a serious problem -  please don't generate one!
                'assetHash'     => Tinebase_Frontend_Http_SinglePageApplication::getAssetHash(),
            ),
            'maxFileUploadSize' => Tinebase_Helper::convertToBytes(ini_get('upload_max_filesize')),
            'maxPostSize'       => Tinebase_Helper::convertToBytes(ini_get('post_max_size')),
            'thousandSeparator' => $symbols['group'],
            'decimalSeparator'  => $symbols['decimal'],
            'brandingWeburl'    => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_WEBURL),
            'brandingLogo'      => Tinebase_ImageHelper::getDataUrl(Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_LOGO)),
            'brandingFaviconSvg' => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON_SVG),
            'brandingTitle'     => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE),
            'brandingDescription'=> Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_DESCRIPTION),
            'brandingHelpUrl'    => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_HELPURL),
            'brandingShopUrl'    => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_SHOPURL),
            'brandingBugsUrl'    => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_BUGSURL),
            'installLogo'       => Tinebase_ImageHelper::getDataUrl(Tinebase_Core::getInstallLogo()),
            'websiteUrl'        => Tinebase_Config::getInstance()->get(Tinebase_Config::WEBSITE_URL),
        );
        
        return $registryData;
    }
}
