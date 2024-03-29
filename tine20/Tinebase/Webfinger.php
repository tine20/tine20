<?php declare(strict_types=1);

class Tinebase_Webfinger
{
    /**
     * Tinebase_Expressive_RoutHandler function
     *
     * @return \Laminas\Diactoros\Response
     */
    public static function handlePublicGet(): \Laminas\Diactoros\Response
    {
        /** @var \Laminas\Diactoros\ServerRequest $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $params = $request->getQueryParams();
        if (!isset($params['resource']) || !isset($params['rel'])) {
            return static::badRequest();
        }
        $resource = $params['resource'];
        $rel = $params['rel'];

        $response = new \Laminas\Diactoros\Response('php://memory', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/jrd+json'
        ]);

        $result = [
            'subject' => $resource,
            'aliases' => [],
            'properties' => [],
            'links' => []
        ];

        $relHandler = Tinebase_Config::getInstance()->{Tinebase_Config::WEBFINGER_REL_HANDLER};
        if (isset($relHandler[$rel])) {
            call_user_func_array($relHandler[$rel], [&$result]);
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }
    
    protected static function badRequest(): \Laminas\Diactoros\Response
    {
        return new \Laminas\Diactoros\Response('php://memory', 400);
    }
}
