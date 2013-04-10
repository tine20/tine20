<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  View
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * View Class
 *
 * @package     Tinebase
 * @subpackage  View
 */
class Tinebase_View
{

    public static function getThemeConfig()
    {
        $extJS     = 'ext-all.css';
        $pathTheme = 'tine20';
        $theme_config = array('','','');
        $favicon = 'images/favicon.ico';

        if(isset(Tinebase_Core::getConfig()->themes->default))
        {
            //set the theme to the one set in the config file
            $numDefaultTheme = Tinebase_Core::getConfig()->themes->default;
            //if the cookie name is defined in the config file and the cookie value is set, 
            //set the theme to the one set in the cookie
            if ((isset(Tinebase_Core::getConfig()->themes->cookieTheme)) &&
            (!empty(Tinebase_Core::getConfig()->themes->cookieTheme)) &&
            (isset($_COOKIE[Tinebase_Core::getConfig()->themes->cookieTheme])))//the cookie of the theme is set
            {
                $numDefaultTheme = $_COOKIE[Tinebase_Core::getConfig()->themes->cookieTheme];
            }
            //if the selected theme exists in the config file, get its path and options
            if (isset(Tinebase_Core::getConfig()->themes->themelist->get($numDefaultTheme)->path))
            {
                $pathTheme = Tinebase_Core::getConfig()->themes->themelist->get($numDefaultTheme)->path;
                //is useBlueAsBase set?
                if ((!isset(Tinebase_Core::getConfig()->themes->themelist->get($numDefaultTheme)->useBlueAsBase)) ||
                        (Tinebase_Core::getConfig()->themes->themelist->get($numDefaultTheme)->useBlueAsBase == 0))
                {
                    $extJS = 'ext-all-notheme.css';
                }
                //is there a customized favicon?
                if (file_exists('themes/' . $pathTheme . '/resources/images/favicon.ico')) 
                {
                    $favicon = 'themes/' . $pathTheme . '/resources/images/favicon.ico';
                }
            }
        }

        $theme_config[0] =  $favicon;
        $theme_config[1] =  '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/'.$extJS.'" />';
        $theme_config[2] =  '<link rel="stylesheet" type="text/css" href="themes/'.$pathTheme.'/resources/css/'.$pathTheme.'.css" />';
        return $theme_config;
    }

}
