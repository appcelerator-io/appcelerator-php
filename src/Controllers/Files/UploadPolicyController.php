<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Files;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * UploadPoliciesController
 */
class UploadPoliciesController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "upload-policy/", $opts);
    }

    /**
     * @param string $uploadPolicyId - Upload Profile Id
     * @param string $key - Unique key for profile used as format argument when resizing media files
     */
    public function createMediaConvertRule(string $uploadPolicyId, string $key, string $matchMimeTypes, int $width, int $height, array $params = [], array $options = [])
    {
        return self::curl("POST", "$uploadPolicyId/media-convert-rule/", array_merge(["mediaConvertRuleId" => $key, "matchMimeTypes" => $matchMimeTypes, "width" => $width, "height" => $height], $params), @$options["headers"] ?? []);
    }

    /**
     * @param string $uploadPolicyId - Upload Profile Id
     * @param string $key - Unique key for profile used as format argument when resizing media files
     */
    public function deleteMediaConvertRule(string $uploadPolicyId, string $key, array $params = [], array $options = [])
    {
        return self::curl("DELETE", "$uploadPolicyId/media-convert-rule/$key/", $params, @$options["headers"] ?? []);
    }
}