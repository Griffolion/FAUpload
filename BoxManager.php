<?php

class BoxManager
{
    private $access_token;
    private $client_id = 'z4cbqfi5tj82xr0wujz7q4cgi88cs6ag';
    private $client_secret = 'XulQuxGrIn87icUI4ZVjva0Dkdjeh3kR';
    private $code;
    private $csrf = 'code22';
    private $error;
    private $error_description;
    private $expires;
    private $refresh_token;
    private $restricted_to = [];
    private $token_type;
    
    private $NO_AUTH = 0;
    private $AUTH_EXPIRED = 1;
    private $AUTH_VALID = 2;
    
    public function BoxManager($code)
    {
        $this->code = $code;
    }
    
    public function getAUTH_VALID()
    {
        return $this->AUTH_VALID;
    }
    
    public function getAUTH_EXPIRED()
    {
        return $this->AUTH_EXPIRED;
    }
    
    public function getNO_AUTH()
    {
        return $this->NO_AUTH;
    }
    
    public function checkAccessToken()
    {
        return isset($this->access_token) ? $this->expires > time() ? $this->AUTH_VALID : $this->AUTH_EXPIRED : $this->NO_AUTH;
    }
    
    public function getAccessToken($refresh = true)
    {
        $url = 'https://app.box.com/api/oauth2/token';
        $parameters = array(
            'grant_type' => $refresh ? 'refresh_token' : 'authorization_code',
        );
        $parameters[$refresh ? 'refresh_token' : 'code'] = $refresh ? $this->refresh_token : $this->code;
        $parameters['client_id'] = 'z4cbqfi5tj82xr0wujz7q4cgi88cs6ag';
        $parameters['client_secret'] = 'XulQuxGrIn87icUI4ZVjva0Dkdjeh3kR';
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->convertParameters($parameters),
            CURLOPT_RETURNTRANSFER => true,
        );
        
        $resp = $this->sendRequest($url, $options);
        $decoded = json_decode($resp);
        if ($this->confirmRequest($decoded)) {
            $this->setAccessTokenRequestVariables($decoded);
            return true;
        } else {
            return $decoded;
        }
    }
    
    public function uploadFile()
    {
        
    }
    
    public function revokeAccess()
    {
        $url = 'https://www.box.com/api/oauth2/revoke';
        $parameters = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'token' => $this->access_token
        );
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->convertParameters($parameters),
            CURLOPT_RETURNTRANSFER => true,
        );
        
        $resp = $this->sendRequest($url, $Options);
        $decoded = json_decode($resp, true);
        if ($this->confirmRequest($decoded)) {
            $this->wipeAccess();
            return true;
        } else {
            return $decoded;
        }
    }
    
    private function confirmFileUpload()
    {
        
    }
    
    private function convertParameters($array)
    {
        $string = '?';
        foreach ($array as $key => $value) {
            $string .= $key . '=' . urlencode($value) . '&';
        }
        rtrim($string, '&');
        return $string;
    }
    
    private function sendRequest($url, $curlOptions)
    {
        $req = curl_init($url);
        curl_setopt_array($req, $curlOptions);
        return curl_exec($req);
    }
    
    private function setAccessTokenRequestVariables($data)
    {
        $this->access_token = $data['access_token'];
        $this->expires = time() + $data['expires_in'];
        $this->restricted_to = $data['restricted_to'];
        $this->token_type = $data['token_type'];
        $this->refresh_token = $data['refresh_token'];
    }
    
    private function wipeAccess()
    {
        $this->access_token = '';
        $this->expires = '';
        $this->restricted_to = '';
        $this->token_type = '';
        $this->refresh_token = '';
    }
    
    private function confirmRequest($response)
    {
        return !(isset($response['error']) && isset($data['error_description']));
    }
}