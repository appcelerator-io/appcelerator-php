<?php
declare(strict_types=1);

namespace AppCelerator\Interfaces;

use AppCelerator\Client;

/**
 * ServiceInterface
 */
interface ServiceInterface
{
    public function getClient() : Client;
    public function getServiceUrl() : string;
}