<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Files;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * UploadProfilesController
 */
class UploadProfilesController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "upload-profile/", $opts);
    }

    /**
     * @param string $uploadProfileId - Upload Profile Id
     * @param string $key - Unique key for profile used as format argument when resizing media files
     */
    public function createMediaConvertRule(string $uploadProfileId, string $key, string $matchMimeTypes, int $width, int $height, array $params = [], array $options = [])
    {
        return self::curl("POST", "$uploadProfileId/media-convert-rule/", array_merge(["mediaConvertRuleId" => $key, "matchMimeTypes" => $matchMimeTypes, "width" => $width, "height" => $height], $params), @$options["headers"] ?? []);
    }

    /**
     * @param string $uploadProfileId - Upload Profile Id
     * @param string $key - Unique key for profile used as format argument when resizing media files
     */
    public function deleteMediaConvertRule(string $uploadProfileId, string $key, array $params = [], array $options = [])
    {
        return self::curl("DELETE", "$uploadProfileId/media-convert-rule/$key/", $params, @$options["headers"] ?? []);
    }
}