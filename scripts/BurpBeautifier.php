#!/usr/bin/env php
<?php

echo "Brup (https://portswigger.net/burp/) XML transcript beautifier \n";
if (! isset($argv[1])) {
    echo "Usage: BurpBeautifier.php inputfile [offset] [limit=1] \n\n";
    die();
}
$xml = @simplexml_load_file($argv[1]);
$offset = isset($argv[2]) ? $argv[2] : 0;
$length = isset($argv[3]) ? $argv[3] : 1;

$result = $xml->xpath("item");
foreach (array_slice($result, $offset, $length) as $item) {
//    echo (string)$item->method . ' ' .  (string)$item->path . "\n";
    echo "\033[34m<-\n" . beautifyHTTPDoc($item->request)  . "\033[0m \n";
    echo "\033[33m->\n" . beautifyHTTPDoc($item->response) . "\033[0m \n";
}

function beautifyHTTPDoc($doc)
{
    list($header, $body) = explode( "\n\n", (string) $doc, 2);
    $beautyString = $header . "\n\n";

    if (preg_match('/Content-Type: (text|application)\/xml/', $header)) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->loadXML($body);
        $beautyString .= $dom->saveXML() . "\n";
    } else {
        $beautyString .= $body;
    }

    return $beautyString;
}

