<?php

use AuthIndicator;
use UploadIndicator;
use AdammBalogh\Box\Command\Content;
use AdammBalogh\Box\Factory\ResponseFactory;
use AdammBalogh\Box\GuzzleHttp\Message\SuccessResponse;
use AdammBalogh\Box\GuzzleHttp\Message\ErrorResponse;
use AdammBalogh\Box\ContentClient;
use AdammBalogh\Box\Client\Content\ApiClient;
use AdammBalogh\Box\Client\Content\UploadClient;
use AdammBalogh\KeyValueStore\KeyValueStore;
use AdammBalogh\KeyValueStore\Adapter\MemoryAdapter;
use AdammBalogh\Box\Exception\ExitException;
use AdammBalogh\Box\Exception\OAuthException;
use GuzzleHttp\Exception\ClientException;
use customOAuthClient;

class BoxManager
{
    const RETURN_URI = 'http://localhost/FAUpload/index.php';
    private $access_token;
    private $client_id = 'z4cbqfi5tj82xr0wujz7q4cgi88cs6ag';
    private $client_secret = 'XulQuxGrIn87icUI4ZVjva0Dkdjeh3kR';
    private $csrf = 'code22';
    private $expires_in;
    private $refresh_token;
    private $restricted_to = [];
    private $token_type;
    private $upload_indicator = UploadIndicator;
    private $auth_indicator = AuthIndicator;
    private $keyValueStore;
    private $oAuthClient;
    
    public function __construct($data)
    {
        $this->auth_indicator = new AuthIndicator();
        $this->upload_indicator = new UploadIndicator();
        $this->keyValueStore = new KeyValueStore(new MemoryAdapter());
        if ($data != NULL && is_array($data)) {
            $this->setFromSession($data);
        }
        $this->oAuthClient = new customOAuthClient($this->keyValueStore, $this->client_id, $this->client_secret, self::RETURN_URI);
        $this->authorize();
        $this->setFromKVS();
    }
    
    public function checkAccessToken()
    {
        return isset($this->access_token) ? $this->expires_in > time() ? $this->auth_indicator->AUTH_VALID : $this->auth_indicator->AUTH_EXPIRED : $this->auth_indicator->NO_AUTH;
    }
    
    public function authorize()
    {
        try {
            $this->oAuthClient->authorize();
        } catch (ExitException $e) {
            # Location header has set (box's authorize page)
            # Instead of an exit call it throws an ExitException
            exit;
        } catch (OAuthException $e) {
            # e.g. Invalid user credentials
            # e.g. The user denied access to your application
        } catch (ClientException $e) {
            # e.g. if $_GET['code'] is older than 30 sec
        }
    }
    
    public function getAccessToken($refresh = true)
    {
        
    }
    
    public function uploadFile()
    {
        $target_dir = 'uploads/';
        $target_file = $target_dir . basename($_FILES['file']['name']);
        $uploadOK = true;
        $fileType = pathinfo($target_file, PATHINFO_EXTENSION);
        
        if (file_exists($target_file)) {
            return $this->upload_indicator->ERROR_FILE_EXISTS;
        }
        
        if ($_FILES['file']['size'] > 500000) {
            return $this->upload_indicator->ERROR_FILE_SIZE_LIMIT_REACHED;
        }
        
        if ($fileType == 'exe') {
            return $this->upload_indicator->ERROR_INVALID_FILE_FORMAT;
        }
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            return syncWithBox($target_file, $_FILES['file']['name']);
        } else {
            return $this->upload_indicator->ERROR_FILE_WAS_NOT_MOVED;
        }
    }
    
    public function syncWithBox($filePath, $fileName)
    {
        $contentClient = new ContentClient(new ApiClient($this->access_token), new UploadClient($this->access_token));
        
        $command = new Content\File\UploadFile($fileName, 0, @$filePath);
        $response = ResponseFactory::getResponse($contentClient, $command);

        if ($response instanceof SuccessResponse) {
            return $this->upload_indicator->UPLOAD_SYNC_SUCCESS;
        } elseif ($response instanceof ErrorResponse) {
            return $this->upload_indicator->UPLOAD_SUCCESS_SYNC_FAIL;
        }
    }
    
    public function revokeAccess()
    {
        $this->oAuthClient->revokeTokens();
        $this->wipeAccess();
    }
    
    private function setFromSession($data)
    {
        $this->keyValueStore->set('access_token', $data['access_token']);
        $this->keyValueStore->set('refresh_token', $data['refresh_token']);
        $this->keyValueStore->set('expires_in', $data['expires_in']);
        $this->keyValueStore->set('restricted_to', $data['restricted_to']);
        $this->keyValueStore->set('token_type', $data['token_type']);
        $this->keyValueStore->expire('access_token', (int)$data['expires_in']);
        $this->keyValueStore->expire('refresh_token', 5184000);
        
        $this->access_token = $data['access_token'];
        $this->expires_in = time() + $data['expires_in'];
        $this->restricted_to = $data['restricted_to'];
        $this->token_type = $data['token_type'];
        $this->refresh_token = $data['refresh_token'];
    }
    
    private function setFromKVS($persist = true)
    {
        $this->access_token = $this->keyValueStore->get('access_token');
        $this->expires_in = $this->keyValueStore->getTtl('access_token');
        $this->restricted_to = $this->keyValueStore->get('restricted_to');
        $this->token_type = $this->keyValueStore->get('token_type');
        $this->refresh_token = $this->keyValueStore->get('refresh_token');
        
        if ($persist) {
            $this->persist();
        }
    }
    
    private function wipeAccess()
    {
        $this->access_token = '';
        $this->expires_in = '';
        $this->restricted_to = '';
        $this->token_type = '';
        $this->refresh_token = '';
        $this->persist();
    }
    
    private function persist()
    {
        $persist = array(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->expires_in,
            'restricted_to' => $this->restricted_to,
            'token_type' => $this->token_type
        );
        session_start();
        $_SESSION['box'] = $persist;
    }
}