<?php

namespace postoor\LineNotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Auth
{
    const AUTHORIZE_URI = '/oauth/authorize';

    const TOKEN_URI = '/oauth/token';

    const REVOKE_URI = '/api/revoke';

    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'clientId' => null,
            'clientSecret' => null,
            'oauthUri' => 'https://notify-bot.line.me',
            'apiUri' => 'https://notify-api.line.me',
        ], $config);

        if ($config['clientId'] == null || $config['clientSecret'] == null) {
            throw new \Exception('clientId/clientSecret Required', 400);
        }

        $this->client = new Client();
    }

    public function getAuthorizeUrl(string $callbackUri, string $state = 'none'): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['clientId'],
            'redirect_uri' => $callbackUri,
            'scope' => 'notify',
            'state' => $state,
        ];

        return $this->config['oauthUri'].Auth::AUTHORIZE_URI.'?'.http_build_query($params);
    }

    public function getToken(string $code, string $callbackUri): string
    {
        $data = [
            'client_id' => $this->config['clientId'],
            'client_secret' => $this->config['clientSecret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $callbackUri,
        ];
        $token = '';

        try {
            $res = $this->client->request('POST', $this->config['oauthUri'].Auth::TOKEN_URI, [
                'form_params' => $data,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            if ($res->getStatusCode() != 200) {
                throw new \Exception("Request Failed: {$res->getBody()}", $res->getResponse()->getStatusCode());
            }

            $data = json_decode((string) $res->getBody(), true);

            $token = $data['access_token'] ?? '';
        } catch (RequestException $error) {
            throw new \Exception("Request Failed: {$error->getResponse()->getBody()}", $error->getResponse()->getStatusCode());
        }

        return $token;
    }

    public function doRevoke(string $token): bool
    {
        try {
            $res = $this->client->request('POST', $this->config['apiUri'].Auth::REVOKE_URI, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Bearer {$token}",
                ],
            ]);

            if ($res->getStatusCode() != 200) {
                throw new \Exception("Request Failed: {$res->getBody()}", $res->getResponse()->getStatusCode());
            }

            $data = json_decode((string) $res->getBody(), true);

            return ($data['status'] ?? '') == 200;
        } catch (RequestException $error) {
            throw new \Exception("Request Failed: {$error->getResponse()->getBody()}", $error->getResponse()->getStatusCode());
        }
    }
}
