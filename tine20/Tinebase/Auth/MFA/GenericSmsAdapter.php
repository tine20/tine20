<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Generic SMS SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_GenericSmsAdapter implements Tinebase_Auth_MFA_AdapterInterface
{
    /** @var Tinebase_Model_MFA_GenericSmsConfig */
    protected $_config;
    protected $_mfaId;
    protected $_httpClientConfig = [];

    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        $this->_config = $_config;
        $this->_mfaId = $id;
    }

    public function setHttpClientConfig(array $_config)
    {
        $this->_httpClientConfig = $_config;
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        $pinLength = (int)$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH};
        if ($pinLength < 3 || $pinLength > 10) throw new Tinebase_Exception('pin length needs to be between 3 and 10');
        $pin = sprintf('%0' . $pinLength .'d', random_int(1, pow(10, $pinLength) - 1));

        $client = Tinebase_Core::getHttpClient($this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_URL},
            $this->_httpClientConfig);
        $client->setMethod($this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD});
        foreach ($this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS} as $header => $value) {
            $client->setHeaders($header, $value);
        }

        $message = Tinebase_Translation::getTranslation()->_('{{ code }} is your {{ app.branding.title }} security code.');
        $message .= '\n\n@{{ app.websiteUrl }} {{ code }}';

        $tbConfig = Tinebase_Config::getInstance();
        $twig = new Twig_Environment(new Twig_Loader_Array());
        $twig->addFilter(new Twig_SimpleFilter('alnum', function($data) {
            return preg_replace('/[^0-9a-zA-Z]+/', '', $data);
        }));
        $twig->addFilter(new Twig_SimpleFilter('gsm7', function(string $data) {
            static $converter = null;
            if (null === $converter) $converter = new BenMorel\GsmCharsetConverter\Converter();
            return $converter->cleanUpUtf8String($data, true);
        }));
        $twig->addFilter(new Twig_SimpleFilter('ucs2', function(string $data) {
            return iconv('ucs-2', 'utf-8', iconv('utf-8', 'ucs-2//TRANSLIT', $data));
        }));

        $message = $twig->createTemplate($message)->render([
            'app' => [
                'websiteUrl'        => Tinebase_Config::getInstance()->get(Tinebase_Config::TINE20_URL),
                'branding'          => [
                    'logo'              => Tinebase_Core::getInstallLogo(),
                    'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                    'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                    'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
                ],
            ],
            'code' => $pin
        ]);
        $client->setRawData($twig->createTemplate($this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY})
            ->render([
                'app' => [
                    'websiteUrl'        => Tinebase_Config::getInstance()->get(Tinebase_Config::TINE20_URL),
                    'branding'          => [
                        'logo'              => Tinebase_Core::getInstallLogo(),
                        'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                        'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                        'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
                    ],
                ],
                'message' => $message,
                'cellphonenumber' => $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}
                    ->{Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER},
            ]));

        $response = $client->request();
        if (200 === $response->getStatus()) {
            try {
                Tinebase_Session::getSessionNamespace()->{static::class} = [
                    'pin' => $pin,
                    'ttl' => time() + (int)$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL}
                ];
            } catch (Zend_Session_Exception $zse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zse->getMessage()) ;
                return false;
            }

            return true;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' failed with status ' .
                $response->getStatus() . ' body: '. $response->getBody());

        return false;
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        if (!is_array($sessionData = Tinebase_Session::getSessionNamespace()->{static::class}) ||
                $sessionData['ttl'] < time() || $sessionData['pin'] !== $_data) {
            return false;
        }
        Tinebase_Session::getSessionNamespace()->{static::class} = null;
        return true;
    }
}
