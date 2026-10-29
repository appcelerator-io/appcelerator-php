<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * OpenIDConnectController
 *  Discovery, signing keys and userinfo. The browser endpoints (authorize, logout) are not part of the SDK.
 */
class OpenIDConnectController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "", $opts);
    }

    /**
     * @param array $options
     * @return array OpenID Provider metadata
     */
    public function discovery(array $options = [])
    {
        return self::curl("GET", ".well-known/openid-configuration", [], @$options["headers"] ?? []);
    }

    /**
     * Public keys to verify the signature of tokens
     * @param array $options
     * @return array [keys]
     */
    public function jwks(array $options = [])
    {
        return self::curl("GET", ".well-known/jwks.json", [], @$options["headers"] ?? []);
    }

    /**
     * Claims of the user of the access token, requires the openid scope (use options['headers']['Authorization'])
     * @param array $options
     * @return array [sub, name, email, ...]
     */
    public function userInfo(array $options = [])
    {
        return self::curl("GET", "userinfo", [], @$options["headers"] ?? []);
    }
}
