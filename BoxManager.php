<?php

include('AuthIndicator.php');
include('UploadIndicator.php');
include('customOAuthClient.php');
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

class BoxManager
{
    const RETURN_URI = 'http://localhost/FAUpload/index.php';
    const CLIENT_ID = 'z4cbqfi5tj82xr0wujz7q4cgi88cs6ag';
    const CLIENT_SECRET = 'XulQuxGrIn87icUI4ZVjva0Dkdjeh3kR';
    const CSRF = 'code22';
    private $access_token;
    private $expires_in;
    private $refresh_token;
    private $restricted_to = [];
    private $token_type;
    private $upload_indicator;
    private $auth_indicator;
    private $keyValueStore;
    private $oAuthClient;
    
    public function __construct($data)
    {
        $this->auth_indicator = new AuthIndicator();
        $this->upload_indicator = new UploadIndicator();
        $this->keyValueStore = new KeyValueStore(new MemoryAdapter());
        if ($data != NULL && is_array($data)) {
            print_r("SESSION_DETECTED | ");
            $this->setFromSession($data);
        }
        $this->oAuthClient = new customOAuthClient($this->keyValueStore, self::CLIENT_ID, self::CLIENT_SECRET, self::RETURN_URI);
        $this->authorize();
        $this->setFromKVS();
    }
    
    public function authorize()
    {
        try {
            print_r("TRY_AUTH | ");
            $this->oAuthClient->authorize();
            print_r("SUCCESSFUL AUTH | ");
        } catch (ExitException $e) {
            print_r("GET CODE DONE");
            # Location header has set (box's authorize page)
            # Instead of an exit call it throws an ExitException
            $this->oAuthClient->authorize();
            print_r("GET TOKEN");
        } catch (OAuthException $e) {
            # e.g. Invalid user credentials
            # e.g. The user denied access to your application
            print_r("FAIL OAUTH EXCEPTION");
        } catch (ClientException $e) {
            print_r("CLIENT EXCEPTION");
            $this->oAuthClient->authorize();
        }
    }
    
    public function uploadFile()
    {
        print_r('BEGIN FILE UPLOAD | ');
        $target_dir = 'uploads/';
        $target_file = $target_dir . basename($_FILES['file']['name']);
        
        if (file_exists($target_file)) {
            print_r('FILE EXISTS | ');
            return $this->syncWithBox($target_file, $_FILES['file']['name']);
        }
        print_r('MOVING FILE | ');
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            print_r('FILE MOVED NOW SYNCING | ');
            return $this->syncWithBox($target_file, $_FILES['file']['name']);
        } else {
            print_r('FILE NOT MOVED | ');
            return $this->upload_indicator->ERROR_FILE_WAS_NOT_MOVED;
        }
    }
    
    public function syncWithBox($filePath, $fileName)
    {
        print_r('BEGIN FILE SYNC | ');
        $contentClient = new ContentClient(new ApiClient($this->access_token), new UploadClient($this->access_token));
        print_r('ATTEMPT UPLOAD | ');
        
        $command = new Content\File\UploadFile($fileName, 0, fopen($filePath, 'c+'));
        
        try {
            $response = ResponseFactory::getResponse($contentClient, $command);
        } catch (\Exception $e) {
            print_r('EXCEPTION | ' . $e);
        }

        if ($response instanceof SuccessResponse) {
            print_r('UPLOAD SUCCESS | ');
            return $this->upload_indicator->UPLOAD_SYNC_SUCCESS;
        } elseif ($response instanceof ErrorResponse) {
            print_r('UPLOAD FAIL | ');
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
        print_r('SETTING_FROM_SESSION | ');
        // $this->keyValueStore->set('access_token', $data['access_token']);
        $this->keyValueStore->set('refresh_token', $data['refresh_token']);
        $this->keyValueStore->set('expires_in', $data['expires_in']);
        $this->keyValueStore->set('restricted_to', $data['restricted_to']);
        $this->keyValueStore->set('token_type', $data['token_type']);
        $this->keyValueStore->expire('access_token', (int)$data['expires_in']);
        $this->keyValueStore->expire('refresh_token', 5184000);
        
        $this->access_token = $data['access_token'];
        $this->expires_in = $data['expires_in'];
        $this->restricted_to = $data['restricted_to'];
        $this->token_type = $data['token_type'];
        $this->refresh_token = $data['refresh_token'];
        print_r('SET_FROM_SESSION | ');
    }
    
    private function setFromKVS($persist = true)
    {
        $this->access_token = $this->oAuthClient->getKvs()->get('access_token');
        $this->expires_in = $this->oAuthClient->getKvs()->getTtl('access_token');
        //$this->restricted_to = $this->oAuthClient->kvs->get('restricted_to');
        //$this->token_type = $this->oAuthClient->kvs->get('token_type');
        $this->refresh_token = $this->oAuthClient->getKvs()->get('refresh_token');
        
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
        $_SESSION['box'] = $persist;
    }
}