<?php declare(strict_types=1);

class SSO_Facade_SAML_RedirectException extends \SimpleSAML\Error\Exception
{
    public $redirectUrl;
    public $data;
    public $binding;

    public function __construct($redirectUrl, $data, $binding)
    {
        $this->redirectUrl = $redirectUrl;
        $this->data = $data;
        $this->binding = $binding;

        parent::__construct('');
    }
}
