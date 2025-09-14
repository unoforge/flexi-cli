<?php

namespace FlexiCli\Utils;

use FlexiCli\Core\Constants;
use Symfony\Component\HttpClient\HttpClient;

class HttpUtils
{
    public static function getJson(string $url, array $headers = [], array $params = []): ?array
    {
        try {
            $client = HttpClient::create();
            
            $options = [];
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }
            if (!empty($params)) {
                $options['query'] = $params;
            }

            $response = $client->request('GET', $url, $options);
            
            if ($response->getStatusCode() !== Constants::HTTP_OK) {
                return null;
            }

            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }
}