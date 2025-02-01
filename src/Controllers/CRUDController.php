<?php
declare(strict_types=1);

namespace AppCelerator\Controllers;

use AppCelerator\Interfaces\ServiceInterface;

/**
 * CRUDController
 */
class CRUDController extends Controller
{
    public function __construct(private ServiceInterface $service, public string $basePath)
    {
        parent::__construct($service, $basePath, $service->getClient()->getKey());
    }

    /**
     * @param mixed $id Entity id
     * @param array $params fields
     * @param array $options
     */
    public function get($id, array $params = [], array $options = [])
    {
        return self::curl("GET", "$id/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param array $params fields, orderBy <name>, orderByDirection <asc,desc>, limit, offset
     * @param array $options
     */
    public function list(array $params = [], array $options = [])
    {
        return self::curl("GET", "", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param mixed params
     * @param mixed options
     */
    public function create($params, $options)
    {
        return self::curl("POST", "", $params, @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id
     * @param array $params
     * @param array $options
     */
    public function update($id, array $params, array $options = [])
    {
        return self::curl("PUT", "$id/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id
     * @param array $params
     * @param array $options
     */
    public function delete($id, array $params = [], array $options = [])
    {
        return self::curl("DELETE", "$id/", $params, @$options["headers"] ?? []);
    }
}