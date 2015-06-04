<?php

use AdammBalogh\Box\Exception\ExitException;


class customOAuthClient extends AdammBalogh\Box\Client\OAuthClient
{
    /*
     * @param KeyValueStore $kvs The kvs to use for storing credentials
     * @param string $clientId The ID of the client application
     * @param string $clientSecret The client secret string
     * @param string $redirectUri The URI to redirect to once auth is complete
     * 
     * Constructor method for the object
     */
    public function __construct(\AdammBalogh\KeyValueStore\KeyValueStore $kvs, $clientId, $clientSecret, $redirectUri) {
        $this->kvs = $kvs;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        parent::__construct($kvs, $clientId, $clientSecret, $redirectUri);
    }
    
    /*
     * Function to get an authorization code from Box
     * Authorization codes are valid for 30 seconds
     * Thus, this function should be called immediately after user allows access
     * 
     * This method is overriden from base oAuthClient class to include CSRF
     * CSRF ('state') is a new development for Box API that isn't reflected in this SDK
     * 
     * @throws ExitException When user is redirected
     */
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
    
    /*
     * @return keyValueStore This oAuthClient object's kvs
     */
    public function getKvs()
    {
        return $this->kvs;
    }
}
