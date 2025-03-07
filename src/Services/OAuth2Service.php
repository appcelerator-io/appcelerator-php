<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * OAuth2Service
 * 
 * @property \AppCelerator\Controllers\OAuth2\UsersController $users
 * @property \AppCelerator\Controllers\OAuth2\ClientsController $clients
 * @property \AppCelerator\Controllers\OAuth2\ScopesController $scopes
 * @property \AppCelerator\Controllers\OAuth2\RolesController $roles
 * @property \AppCelerator\Controllers\OAuth2\AuthorizationCodeController $authorizationCodes
 */
class OAuth2Service extends Service
{
    public function __construct(private Client $client)
    {
        parent::__construct($client, "OAuth2");
    }
}