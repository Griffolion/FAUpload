<?php

class UploadIndicator
{
    public function __CONSTRUCT()
    {
        
    }
    
    const UPLOAD_SYNC_SUCCESS = 0;
    const UPLOAD_SUCCESS_SYNC_FAIL = 1;
    const ERROR_FILE_EXISTS = 2;
    const ERROR_FILE_SIZE_LIMIT_REACHED = 3;
    const ERROR_INVALID_FILE_FORMAT = 4;
    const ERROR_FILE_WAS_NOT_MOVED = 5;
}
