<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * IdentityProvidersController
 *  Extends Controller instead of CRUDController, create requires more arguments than CRUDController::create allows
 */
class IdentityProvidersController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "identity-provider/", $opts);
    }

    /**
     * @param string $identityProviderId ID of identity provider
     * @param array $params fields
     * @param array $options
     */
    public function get($identityProviderId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$identityProviderId/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param array $params search, fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     */
    public function list(array $params = [], array $options = [])
    {
        return self::curl("GET", "", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $name Name shown on the login page e.g. Google
     * @param string $issuer Issuer (iss) of the identity provider e.g. https://accounts.google.com
     * @param string $clientId Client ID received from the identity provider
     * @param array $params clientSecret, scope, audiences, trustEmail, createUsers, enabled
     * @param array $options
     */
    public function create($name, $issuer, $clientId, array $params = [], array $options = [])
    {
        $params = array_merge(["name" => $name, "issuer" => $issuer, "clientId" => $clientId], $params);

        return self::curl("POST", "", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $identityProviderId ID of identity provider
     * @param array $params name, issuer, clientId, clientSecret, scope, audiences, trustEmail, createUsers, enabled
     * @param array $options
     */
    public function update($identityProviderId, array $params, array $options = [])
    {
        return self::curl("PUT", "$identityProviderId/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $identityProviderId ID of identity provider
     * @param array $options
     */
    public function delete($identityProviderId, array $options = [])
    {
        return self::curl("DELETE", "$identityProviderId/", [], @$options["headers"] ?? []);
    }
}
