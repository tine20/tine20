<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Frontend_Http_SinglePageApplication {

    /**
     * generates initial client html
     *
     * @param string|array  $entryPoint
     * @param string        $template
     * @return \Zend\Diactoros\Response
     */
    public static function getClientHTML($entryPoint, $template='Tinebase/views/singlePageApplication.html.twig', $context = []) {
        $entryPoints = is_array($entryPoint) ? $entryPoint : [$entryPoint];

        $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation('Tinebase'));
        $twig->getEnvironment()->addFunction(new Twig_SimpleFunction('jsInclude', function ($file) {
            $fileMap = self::getAssetsMap();
            if (isset($fileMap[$file]['js'])) {
                $file = $fileMap[$file]['js'];
            } else {
                $file .= (strpos($file, '?') ? '&' : '?') . 'version=' . Tinebase_Frontend_Http_SinglePageApplication::getAssetHash();
            }

            $baseUrl = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NO_PROTO);

            if (TINE20_BUILDTYPE == 'DEBUG') {
                $file = preg_replace('/\.js$/', '.debug.js', $file);
            }

            return '<script type="text/javascript" src="' . $baseUrl . '/' . $file .'"></script>';
        }, ['is_safe' => ['all']]));

        $textTemplate = $twig->load($template);

        $context += [
            'assetHash' => Tinebase_Frontend_Http_SinglePageApplication::getAssetHash(),
            'jsFiles' => $entryPoints,
        ];

        return new \Zend\Diactoros\Response\HtmlResponse($textTemplate->render($context), 200, self::getHeaders());
    }

    /**
     * gets headers for initial client html pages
     *
     * @return array
     */
    public static function getHeaders()
    {
        $header = [];

        $frameAncestors = implode(' ' ,array_merge(
            (array) Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDJSONORIGINS, array()),
            array("'self'")
        ));

        // set Content-Security-Policy header against clickjacking and XSS
        // @see https://developer.mozilla.org/en/Security/CSP/CSP_policy_directives
        $scriptSrcs = array("'self'", "'unsafe-eval'", 'https://versioncheck.tine20.net');
        if (TINE20_BUILDTYPE == 'DEVELOPMENT') {
            $scriptSrcs[] = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PROTOCOL) . '://' .
                Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) . ":10443";
        }
        $scriptSrc = implode(' ', $scriptSrcs);
        $header += [
            "Content-Security-Policy" => "default-src 'self'",
            "Content-Security-Policy" => "script-src $scriptSrc",
            "Content-Security-Policy" => "frame-ancestors $frameAncestors",

            // headers for IE 10+11
            "X-Content-Security-Policy" => "default-src 'self'",
            "X-Content-Security-Policy" => "script-src $scriptSrc",
            "X-Content-Security-Policy" => "frame-ancestors $frameAncestors",
        ];

        // set Strict-Transport-Security; used only when served over HTTPS
        $headers['Strict-Transport-Security'] = 'max-age=16070400';

        // cache mainscreen for one day in production
        $maxAge = ! defined('TINE20_BUILDTYPE') || TINE20_BUILDTYPE != 'DEVELOPMENT' ? 86400 : -10000;
        $header += [
            'Cache-Control' => 'private, max-age=' . $maxAge,
            'Expires' => gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT",
        ];

        return $header;
    }

    /**
     * get map of asset files
     *
     * @param boolean $asJson
     * @throws Exception
     * @return string|array
     */
    public static function getAssetsMap($asJson = false)
    {
        $jsonFile = self::getAssetsJsonFilename();

        if (TINE20_BUILDTYPE =='DEVELOPMENT') {
            $devServerURL = Tinebase_Config::getInstance()->get('webpackDevServerURL', 'http://localhost:10443');
            $jsonFileUri = $devServerURL . '/' . $jsonFile;
            $json = Tinebase_Helper::getFileOrUriContents($jsonFileUri);
            if (! $json) {
                Tinebase_Core::getLogger()->ERR(__CLASS__ . '::' . __METHOD__ . ' (' . __LINE__ .') Could not get json file: ' . $jsonFile);
                throw new Exception('You need to run webpack-dev-server in dev mode! See https://wiki.tine20.org/Developers/Getting_Started/Working_with_GIT#Install_webpack');
            }
        } else if ($absoluteJsonFilePath = self::getAbsoluteAssetsJsonFilename()) {
            $json = file_get_contents($absoluteJsonFilePath);
        } else {
            throw new Tinebase_Exception_NotFound(('assets json not found'));
        }

        return $asJson ? $json : json_decode($json, true);
    }

    /**
     * @return string
     */
    public static function getAssetsJsonFilename()
    {
        return 'Tinebase/js/webpack-assets-FAT.json';
    }

    /**
     * @return string|null
     */
    public static function getAbsoluteAssetsJsonFilename()
    {
        $path = __DIR__ . '/../../../' . self::getAssetsJsonFilename();
        if (! file_exists($path)) {
            return null;
        }
        return $path;
    }

    /**
     *
     * @param  bool     $userEnabledOnly    this is needed when server concats js
     * @return string
     * @throws Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getAssetHash($userEnabledOnly = false)
    {
        $map = self::getAssetsMap();

        if ($userEnabledOnly) {
            $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
            foreach($map as $asset => $ressources) {
                if (! $enabledApplications->filter('name', basename($asset))->count()) {
                    unset($map[$asset]);
                }
            }
        }

        return sha1(json_encode($map) . TINE20_BUILDTYPE);
    }
}