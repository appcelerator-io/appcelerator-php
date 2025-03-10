<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * OAuth2Service
 * 
 * @property \AppCelerator\Controllers\Oauth2\UsersController $users
 * @property \AppCelerator\Controllers\Oauth2\ClientsController $clients
 * @property \AppCelerator\Controllers\Oauth2\ScopesController $scopes
 * @property \AppCelerator\Controllers\Oauth2\RolesController $roles
 * @property \AppCelerator\Controllers\Oauth2\AuthorizationCodesController $authorizationCodes
 */
class OAuth2Service extends Service
{
    public function __construct(private Client $client)
    {
        parent::__construct($client, "Oauth2");
    }
}