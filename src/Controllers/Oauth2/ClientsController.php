<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * ClientsController
 */
class ClientsController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "client/", $opts);   
    }

    /**
     * @param string $name Client name
     */
    public function create($name, $params = [], array $options = [])
    {
        $params = array_merge(["name" => $name, $params]);

        return self::curl("POST", "", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param string $clientSecret Secret of client
     */
    public function authenticate($clientId, $clientSecret, array $params = [], array $options = [])
    {
        $params = array_merge([
            "clientId" => $clientId,
            "clientSecret" => $clientSecret,
        ], $params);

        return self::curl("POST", "authenticate", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     */
    public function renewClientSecret($clientId, array $options = [])
    {
        return self::curl("POST", "$clientId/renew-secret", [], @$options["headers"] ?? []);
    }

    /**
     * @param array $options
     * @param array $options
     * @return array
     */
    public function logs(array $params = [], array $options = [])
    {
        return self::curl("GET", "log/", $params, @$options["headers"] ?? []);
    }
}