<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * SecretsController
 *  Secrets belong to a client: client/{clientId}/secret/
 */
class SecretsController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "client/", $opts);
    }

    /**
     * @param string $clientId ID of client
     * @param string $secretId ID of secret
     * @param array $params fields
     * @param array $options
     */
    public function get($clientId, $secretId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$clientId/secret/$secretId/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $clientId ID of client
     * @param array $params search, fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     */
    public function list($clientId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$clientId/secret/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * The secret value is only returned once, in the response of create
     * @param string $clientId ID of client
     * @param array $params name, description, expires
     * @param array $options
     */
    public function create($clientId, array $params = [], array $options = [])
    {
        return self::curl("POST", "$clientId/secret/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param string $secretId ID of secret
     * @param array $params name, description
     * @param array $options
     */
    public function update($clientId, $secretId, array $params, array $options = [])
    {
        return self::curl("PUT", "$clientId/secret/$secretId/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param string $secretId ID of secret
     * @param array $options
     */
    public function delete($clientId, $secretId, array $options = [])
    {
        return self::curl("DELETE", "$clientId/secret/$secretId/", [], @$options["headers"] ?? []);
    }
}
