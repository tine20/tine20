<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Message\ResponseInterface;
use \Zend\Diactoros\Response\EmitterInterface;

/**
 * Test helper Emitter
 *
 * @package     Tinebase
 */
class Tinebase_Server_UnittestEmitter implements EmitterInterface
{

    /**
     * @var ResponseInterface|null
     */
    public $response = null;

    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response)
    {
        $this->response = $response;
    }
}