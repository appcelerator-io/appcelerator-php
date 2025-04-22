<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Files;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * StoragesController
 */
class StoragesController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "storage/", $opts);
    }
}