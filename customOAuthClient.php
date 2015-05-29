<?php

namespace AdammBalogh\Box\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\ResponseInterface;
use AdammBalogh\KeyValueStore\KeyValueStore;
use GuzzleHttp\Exception\ClientException;
use AdammBalogh\Box\Exception\ExitException;
use AdammBalogh\Box\Exception\OAuthException;
use AdammBalogh\KeyValueStore\Exception\KeyNotFoundException;

class customOAuthClient extends AdammBalogh\Box\Client\OAuthClient
{
    protected function getCode()
    {
        $queryData = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => 'code22'
        ];

        header('Location: ' . self::AUTHORIZE_URI . '?' . http_build_query($queryData));
        throw new ExitException();
    }
}
