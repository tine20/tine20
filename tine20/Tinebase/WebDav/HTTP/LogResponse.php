<?php

/**
 * Class Tinebase_WebDav_HTTP_LogResponse
 *
 * NOTE: we can't decide logging before content-type header is set
 */
class Tinebase_WebDav_HTTP_LogResponse extends Sabre\HTTP\Response
{
    protected $_bodyLog = false;

    protected $_obActive = false;

    /**
     * start body logging
     * @param $bool
     */
    public function startBodyLog($bool)
    {
        $this->_bodyLog = $bool;
    }

    /**
     * start body logging
     * @return string
     */
    public function stopBodyLog()
    {
        $body = '--- BODY DEBUG WAS NOT STARTED ---';
        if ($this->_bodyLog) {
            if ($this->_obActive) {
                $body = ob_get_contents();
                ob_end_flush();
            } else {
                $body = '-- BINARY DATA --';
            }
        }

        return $body;
    }

    /**
     * Sets an HTTP header for the response
     *
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return bool
     */
    public function setHeader($name, $value, $replace = true)
    {
        if (strtolower($name) == 'content-type'
            && stripos($value,'text') === 0 || stripos($value, '/xml') !== false
            && $this->_bodyLog) {

            ob_start();
            $this->_obActive = true;
        }

        return parent::setHeader($name, $value, $replace);
    }
}