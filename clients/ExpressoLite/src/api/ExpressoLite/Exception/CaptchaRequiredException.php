<?php
/**
 * Tine Tunnel
 * Exception thrown when Tine fails a login because it requires the user
 * to inform a CAPTCHA.
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class CaptchaRequiredException extends LiteException
{
    /**
     * @var string $catcha Base 64 encoded CAPTCHA image
     */
    private $captcha;

    /**
     * Creates a new <tt>CaptchaRequiredException</tt>
     *
     * @param string $catcha Base 64 encoded CAPTCHA image.
     */
    public function __construct($captcha)
    {
        parent::__construct('CAPTCHA authentication required', self::HTTP_401_UNAUTHORIZED);
        $this->captcha = $captcha;
    }

    /**
     * @return string $catcha Base 64 encoded CAPTCHA image
     */
    public function getCaptcha()
    {
        return $this->captcha;
    }
}
