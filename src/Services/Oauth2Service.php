<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * OAuth2Service
 * 
 * @property \AppCelerator\Controllers\Oauth2\TokenController $token
 * @property \AppCelerator\Controllers\Oauth2\UsersController $users
 * @property \AppCelerator\Controllers\Oauth2\ClientsController $clients
 * @property \AppCelerator\Controllers\Oauth2\SecretsController $secrets
 * @property \AppCelerator\Controllers\Oauth2\ScopesController $scopes
 * @property \AppCelerator\Controllers\Oauth2\RolesController $roles
 * @property \AppCelerator\Controllers\Oauth2\IdentityProvidersController $identityProviders
 * @property \AppCelerator\Controllers\Oauth2\UserIdentitiesController $userIdentities
 * @property \AppCelerator\Controllers\Oauth2\VerificationController $verification
 * @property \AppCelerator\Controllers\Oauth2\OpenIDConnectController $openIDConnect
 * @property \AppCelerator\Controllers\Oauth2\AuthorizationCodesController $authorizationCodes
 */
class OAuth2Service extends Service
{
    public function __construct(private Client $client, array $opts = [])
    {
        parent::__construct($client, "Oauth2", $opts);
    }
}