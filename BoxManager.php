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
    private $upload_indicator;
    private $auth_indicator;
    private $keyValueStore;
    private $oAuthClient;
    private $contentClient;
    
    /*
     * Constructor method will initialize Auth objects
     * Will attempt to authorize and set session variables with new auth credentials
     * 
     * @param array|NULL $data Session array to set old auth credentials
     */
    public function __construct($data)
    {
        $this->auth_indicator = new AuthIndicator();
        $this->upload_indicator = new UploadIndicator();
        $this->keyValueStore = new KeyValueStore(new MemoryAdapter());
        if ($data != NULL && is_array($data)) {
            $this->setFromSession($data);
        }
        $this->authorize();
    }
    
    /*
     * Calls oAuth Client object to authorize
     * Retries if exceptions are given
     */
    public function authorize()
    {
        $this->oAuthClient = new customOAuthClient($this->keyValueStore, self::CLIENT_ID, self::CLIENT_SECRET, self::RETURN_URI);
        try {
            $this->oAuthClient->authorize();
        } catch (ExitException $e) {
            # Location header has set (box's authorize page)
            # Instead of an exit call it throws an ExitException
            $this->oAuthClient->authorize();
        } catch (OAuthException $e) {
            # e.g. Invalid user credentials
            # e.g. The user denied access to your application
        } catch (ClientException $e) {
            $this->oAuthClient->authorize();
        }
        $this->setFromKVS();
        $this->contentClient = new ContentClient(new ApiClient($this->access_token), new UploadClient($this->access_token));
    }
    
    /*
     * Attempts to upload file to server folder
     * Will call Box sync method if file already exists/has been moved successfully
     * 
     * @return int The success/failure indicator
     */
    public function uploadFile()
    {
        $target_dir = 'uploads/';
        $target_file = $target_dir . basename($_FILES['file']['name']);
        
        if (file_exists($target_file)) {
            if ($this->fileExistsInBoxFolder($_FILES['file']['name'])){
                return true; // todo
            }
            return $this->syncWithBox($target_file, $_FILES['file']['name'], $_POST['folder']);
        } else if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            return $this->syncWithBox($target_file, $_FILES['file']['name'], $_POST['folder']);
        } else {
            return UploadIndicator::ERROR_FILE_WAS_NOT_MOVED;
        }
    }
    
    /*
     * @param string @filePath The filepath of the file in question
     * @param string @fileName The name of the file in question
     * 
     * Will attempt to sync with Box 
     * 
     * @return int The success/failure indicator
     */
    public function syncWithBox($filePath, $fileName, $boxFolder = 0)
    {   
        $command = new Content\File\UploadFile($fileName, $boxFolder, fopen($filePath, 'c+'));
        
        try {
            $response = ResponseFactory::getResponse($this->contentClient, $command);
        } catch (\Exception $e) {
            print_r($e);
        }

        if ($response instanceof SuccessResponse) {
            return UploadIndicator::UPLOAD_SYNC_SUCCESS;
        } elseif ($response instanceof ErrorResponse) {
            return UploadIndicator::UPLOAD_SUCCESS_SYNC_FAIL;
        }
    }
    
    /*
     * @param string @root The id of the root folder to check within
     * 
     * Enumerate the folders within given folder of user's Box account
     * Root defaults to Box account's top level folder "All Files" (id: 0)
     * 
     * @return array @Array Key-Value array of folders or top level folder if error
     */
    public function getFolders($folder = '0')
    {
        $response = $this->getFolderInfo($folder);
        $folders = [];
        if ($folder == '0') {
            $folders['Root Folder'] = '0';
        }
        if ($response !== NULL) {
            foreach ($response["item_collection"]["entries"] as $entry) {
                if ($entry["type"] === 'folder') {
                    $folders[$entry['name']] = $entry['id'];
                }
            }
            return $folders;
        }
        return array('Root Folder' => '0');
    }
    
    /*
     * @param string $fileName String with name of the file
     * @param string $folder String with the folder ID in Box
     * 
     * Will check if the file with the given name exists in the given Box folder
     * 
     * @return boolean True if the file exists, false if it doesn't
     */
    public function fileExistsInBoxFolder($fileName, $folder = '0')
    {
        $response = $this->getFolderInfo($folder);
        if ($response !== NULL) {
            foreach ($response['item_collection']['entries'] as $entry) {
                if ($entry['type'] === 'file' && $entry['name'] === $fileName) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /*
     * @param string $folder String with the folder ID in Box
     * 
     * Gets the informational JSON of the given folder from Box
     * 
     * @return array|NULL Returns json_decode() output if successful response
     *                    Or NULL if error response
     */
    private function getFolderInfo($folder = '0')
    {
        $command = new Content\Folder\GetFolderInfo($folder);
        $response = ResponseFactory::getResponse($this->contentClient, $command);
        
        if ($response instanceof SuccessResponse) {
            return $response->json();
        } elseif ($response instanceof ErrorResponse) {
            return NULL;
        }
    }
    
    /*
     * Revokes access tokens in the oAuth client object
     * Calls wipe access to remove locally stored credentials
     */
    public function revokeAccess()
    {
        $this->oAuthClient->revokeTokens();
        $this->wipeAccess();
    }
    
    /*
     * @param array() $data The session array
     * 
     * Sets kvs and local variables from session data
     */
    private function setFromSession($data)
    {
        $this->keyValueStore->set('access_token', $data['access_token']);
        $this->keyValueStore->set('refresh_token', $data['refresh_token']);
        $this->keyValueStore->set('expires_in', $data['expires_in']);
        $this->keyValueStore->expire('access_token', (int)$data['expires_in']);
        $this->keyValueStore->expire('refresh_token', 5184000);
        
        $this->access_token = $data['access_token'];
        $this->expires_in = $data['expires_in'];
        $this->refresh_token = $data['refresh_token'];
    }
    
    /*
     * @param boolean $persist Flag to indicate whether session should be updated
     * 
     * Sets local variables from kvs
     * Persist to session if flag is set true (it is by default)
     */
    private function setFromKVS($persist = true)
    {
        $this->access_token = $this->oAuthClient->getKvs()->get('access_token');
        $this->expires_in = $this->oAuthClient->getKvs()->getTtl('access_token');
        $this->refresh_token = $this->oAuthClient->getKvs()->get('refresh_token');
        
        if ($persist) {
            $this->persist();
        }
    }
    
    /*
     * Gets rid of credentials from session so user is forced to re-auth manually
     */
    private function wipeAccess()
    {
        unset($_SESSION['box']);
    }
    
    /*
     * Puts credentials into array
     * Array is set as a session variable 'box'
     */
    private function persist()
    {
        $persist = array(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->expires_in,
        );
        $_SESSION['box'] = $persist;
    }
}