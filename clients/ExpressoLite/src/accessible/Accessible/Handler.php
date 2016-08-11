<?php
/**
 * Expresso Lite Accessible
 * Abstract class that defines expected behavior of http requests
 * handlers created by Dispatcher. Also provides some utility methods.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible;

use \ReflectionClass;
use ExpressoLite\Exception\LiteException;

abstract class Handler
{
    /**
     * This is the main method of this class, which will execute all
     * logic related to the request. Needless to say, all subclasses
     * must necessarily implement it.
     *
     * @param  stdClass  $params Parameters to be forwarded.
     * @return The request response.
     */
    abstract function execute($params);

    /**
     * Gets current directory path of this class.
     *
     * @return string Path to class.
     */
    private function getCurrentClassDir()
    {
        $classname = get_class($this);
        $namespace = substr($classname, 0, strrpos($classname, "\\"));
        return str_replace("\\", '/', $namespace);
    }

    /**
     * Fills a template generating an HTML page.
     *
     * @param string   $templateName Name of the source template.
     * @param stdClass $VIEW         Object with member variables to fill the template.
     */
    public function showTemplate($templateName, $VIEW = null)
    {
        if(!ctype_alpha($templateName)) { //only alphabetic chars
            throw new LiteException('Invalid template name: ' . $templateName);
        }

        $templateFile = $this->getCurrentClassDir() . '/Template/' . $templateName . '.php';
        if (!file_exists($templateFile)) {
            throw new LiteException('Template not found: ' . $templateName);
        }

        include $templateFile;
    }

    /**
     * Assemblies an URL with parameters to be used on the template.
     *
     * @param  string $action Action to be performed, like "Mail.Delete".
     * @param  array  $params Parameters to be forwarded.
     * @return string         URL ready to be used on the template.
     */
    public function makeUrl($action, array $params = array())
    {
        $retUrl = './?r=' . $action;
        foreach ($params as $key => $val) {
            $retUrl .= '&' . urlencode($key) . '=' . urlencode($val);
        }
        return $retUrl;
    }
}
