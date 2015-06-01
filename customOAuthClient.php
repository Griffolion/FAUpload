<?php

use AdammBalogh\Box\Exception\ExitException;


class customOAuthClient extends AdammBalogh\Box\Client\OAuthClient
{
    public function __construct(\AdammBalogh\KeyValueStore\KeyValueStore $kvs, $clientId, $clientSecret, $redirectUri) {
        $this->kvs = $kvs;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        parent::__construct($kvs, $clientId, $clientSecret, $redirectUri);
    }
    protected function getCode()
    {
        $queryData = [
            'response_type' => urlencode('code'),
            'client_id' => urldecode($this->clientId),
            'redirect_uri' => $this->redirectUri,
            'state' => urlencode('code22'),
        ];

        header('Location: ' . self::AUTHORIZE_URI . '?' . http_build_query($queryData));
        throw new ExitException();
    }
    
    public function getKvs()
    {
        return $this->kvs;
    }
}
