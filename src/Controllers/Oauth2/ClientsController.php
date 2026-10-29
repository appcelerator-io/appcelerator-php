<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * ClientsController
 *  Extends Controller instead of CRUDController, create requires more arguments than CRUDController::create allows
 */
class ClientsController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "client/", $opts);
    }

    /**
     * Retrieve client data based on the provided token
     * @param array $params fields
     * @param array $options
     */
    public function self(array $params = [], array $options = [])
    {
        return self::curl("GET", "self", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param array $params fields
     * @param array $options
     */
    public function get($clientId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$clientId/", $params, @$options["headers"] ?? [])["data"];
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
     * @param string $name Client name e.g. my-web-app, my-backend-api, my-mobile-app
     * @param string $grantTypes Authentication flows supported by client
     * @param string $redirectURI Redirect URI used for processing token and code responses
     * @param array $params
     * @param array $options
     */
    public function create($name, $grantTypes, $redirectURI, array $params = [], array $options = [])
    {
        $params = array_merge(["name" => $name, "grantTypes" => $grantTypes, "redirectURI" => $redirectURI], $params);

        return self::curl("POST", "", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param array $params grantTypes, name, redirectURI, website, logoURI
     * @param array $options
     */
    public function update($clientId, array $params, array $options = [])
    {
        return self::curl("PUT", "$clientId/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param array $options
     */
    public function delete($clientId, array $options = [])
    {
        return self::curl("DELETE", "$clientId/", [], @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param array $params fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     * @return array
     */
    public function logs($clientId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$clientId/log/", $params, @$options["headers"] ?? [])["data"];
    }
}
