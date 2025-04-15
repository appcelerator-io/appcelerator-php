<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * FilesService
 * 
 * @property \AppCelerator\Controllers\Files\FilesController $files
 * @property \AppCelerator\Controllers\Files\StoragesController $storages
 * @property \AppCelerator\Controllers\Files\UploadProfilesController $uploadProfiles
 */
class FilesService extends Service
{
    public function __construct(private Client $client)
    {
        parent::__construct($client, "Files");
    }
}