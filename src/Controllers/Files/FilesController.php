<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Files;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * FilesController
 */
class FilesController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "", $opts);
    }

    /**
     * @param mixed $id Entity id
     * @param array $params fields
     * @param array $options
     */
    public function get($id, array $params = [], array $options = [])
    {
        return self::curl("GET", "file/$id/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param mixed $id Entity id
     * @param array $params storageId, fileId, search, tags, contentType, subjectId, public, fields, limit, offset, options
     * @param array $options
     */
    public function list($id, array $params = [], array $options = [])
    {
        return self::curl("GET", "file/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $code Authorization code
     * @param array $options
     */
    public function uploadFile($storageId, $key, $mimeType, array $params = [], array $options = [])
    {
        return self::curl("POST", "storage/$storageId/file/upload", array_merge(["key" => $key, "mimeType" => $mimeType], $params), @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id Entity id
     * @param array $params
     * @param array $options
     */
    public function delete($id, array $params = [], array $options = [])
    {
        return self::curl("DELETE", "file/$id/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id Entity id
     * @param array $params
     * @param array $options
     */
    public function deleteFiles($storageId, array $params = [], array $options = [])
    {
        return self::curl("DELETE", "storage/$storageId/file/", $params, @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id Entity id
     * @param mixed $formats comma separated formats, e.g. small,medium,large
     * @param array $params
     * @param array $options
     */
    public function convert($id, string $formats, array $params = [], array $options = [])
    {
        return self::curl("POST", "file/$id/convert/", array_merge(["formats" => $formats], $params), @$options["headers"] ?? []);
    }

    /**
     * @param mixed $id Entity id
     * @param array $params fields
     * @param array $options
     */
    public function listMedia(array $params = [], array $options = [])
    {
        return self::curl("GET", "media/", $params, @$options["headers"] ?? [])["data"];
    }
}