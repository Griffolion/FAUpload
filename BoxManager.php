<?php

class BoxManager
{
    private $code;
    private $csrf;
    private $access_token;
    private $refresh_token;
    private $expires;
    private $client_id = 'z4cbqfi5tj82xr0wujz7q4cgi88cs6ag';
    private $client_secret = 'XulQuxGrIn87icUI4ZVjva0Dkdjeh3kR';
    
    
    public function BoxManager()
    {
        
    }
    
    public function isExistingAuth()
    {
        
    }
    
    public function isNewAuth()
    {
        
    }
    
    public function getAccessToken($refresh = true)
    {
        $parameters = array(
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'client_id' => ,
            'client_secret' => ,
        );
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ''
        );
        curl_init('https://app.box.com/api/oauth2/token');
        
    }
}