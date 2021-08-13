<?php declare(strict_types=1);

class SSO_Facade_SAML_RedirectException extends \SimpleSAML\Error\Exception
{
    public $redirectUrl;
    public $data;

    public function __construct($redirectUrl, $data)
    {
        $this->redirectUrl = $redirectUrl;
        $this->data = $data;

        parent::__construct('');
    }
}
