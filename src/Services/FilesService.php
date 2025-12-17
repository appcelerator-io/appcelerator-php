<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * FilesService
 * 
 * @property \AppCelerator\Controllers\Files\FilesController $files
 * @property \AppCelerator\Controllers\Files\StoragesController $storages
 * @property \AppCelerator\Controllers\Files\UploadPoliciesController $uploadPolicies
 */
class FilesService extends Service
{
    public function __construct(private Client $client, array $opts = [])
    {
        parent::__construct($client, "Files", $opts);
    }
}