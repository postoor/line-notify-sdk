<?php

namespace postoor\LineNotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class Message
{
    const NOTIFY_URI = '/api/notify';

    const STATUS_URI = '/api/status';

    protected $config = [];

    protected $client;

    public $rateLimit = [
        'limit' => null,
        'remaining' => null,
        'imageLimit' => null,
        'imageRemaining' => null,
        'reset' => null,
    ];

    public $apiRateLimitHeaders = [
        'X-RateLimit-Limit' => 'limit',
        'X-RateLimit-Remaining' => 'remaining',
        'X-RateLimit-ImageLimit' => 'imageLimit',
        'X-RateLimit-ImageRemaining' => 'imageRemaining',
        'X-RateLimit-Reset' => 'reset',
    ];

    public function __construct(string $token, string $apiUri = '')
    {
        $this->config = [
            'token' => $token,
            'apiUri' => $apiUri ?: 'https://notify-api.line.me',
        ];

        $this->client = new Client([
            'headers' => [
              'Authorization' => "Bearer {$token}",
            ],
        ]);
    }

    public function send(string $message, array $extraParams = []): bool
    {
        $data = array_merge([
            'message' => $message,
            'imageThumbnail' => null,
            'imageFullsize' => null,
            'imageFile' => null,
            'stickerPackageId' => null,
            'stickerId' => null,
            'notificationDisabled' => null,
        ], $extraParams);
        $requestOptions = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        try {
            foreach ($data as $label => $param) {
                if ($label == 'notificationDisabled' && $param !== null) {
                    continue;
                }
                if ($param == null) {
                    unset($data[$label]);
                }
            }

            $requestOptions['form_params'] = $data;

            if ($data['imageFile'] ?? false) {
                if (!is_file($data['imageFile'])) {
                    throw new \Exception("{$data['imageFile']} Not A Correct Format", 400);
                }

                $data['imageFile'] = fopen($data['imageFile'], 'r');
                unset($data['imageThumbnail']);
                unset($data['imageFullsize']);

                $multipart = [];
                foreach ($data as $key => $value) {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
                $requestOptions['multipart'] = $multipart;
                unset($requestOptions['headers']['Content-Type']);
                unset($requestOptions['form_params']);
            }

            $res = $this->client->request('POST', $this->config['apiUri'].Message::NOTIFY_URI, $requestOptions);
            if ($res->getStatusCode() != 200) {
                throw new \Exception("Request Failed: {$res->getBody()}", $res->getStatusCode());
            }

            $this->recordAPIRateLimit($res);

            $resData = json_decode((string) $res->getBody(), true);

            return $resData['status'] == 200;
        } catch (RequestException $error) {
            throw new \Exception("Request Failed: {$error->getResponse()->getBody()}", $error->getResponse()->getStatusCode());
        }
    }

    public function checkStatus(): bool
    {
        try {
            $res = $this->getStatus();

            return ($res['status'] ?? 0) == 200;
        } catch (\Exception $error) {
            if ($error->getCode() == 401) {
                return false;
            }

            throw new \Exception($error->getMessage(), $error->getCode());
        }
    }

    public function getStatus(): array
    {
        $resData = [];
        try {
            $res = $this->client->request('GET', $this->config['apiUri'].Message::STATUS_URI);

            if ($res->getStatusCode() != 200) {
                throw new \Exception("Request Failed: {$res->getBody()}", $res->getResponse()->getStatusCode());
            }

            $this->recordAPIRateLimit($res);

            $resData = json_decode((string) $res->getBody(), true);
        } catch (RequestException $error) {
            throw new \Exception("Request Failed: {$error->getResponse()->getBody()}", $error->getResponse()->getStatusCode());
        }

        return $resData;
    }

    private function recordAPIRateLimit(ResponseInterface $res): void
    {
        foreach ($this->apiRateLimitHeaders as $headerName => $limitKey) {
            $this->rateLimit[$limitKey] = $res->getHeader($headerName);
        }
    }
}
