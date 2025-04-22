<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * ScopesController
 */
class ScopesController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "scope/", $opts);
    }

    /**
     * Returns every scope assigned to subject with ID
     * 
     * @param string $subjectId ID of subject
     * @param array $options
     */
    public function subject($subjectId, array $options = [])
    {
        return self::curl("GET", "subject", ["subjectId" => $subjectId], @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or name
     * @param array $options
     */
    public function assign($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "assign", ["subjectId" => $subjectId, "scope" => $scope], @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or name
     * @param array $options
     */
    public function revoke($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "revoke", ["subjectId" => $subjectId, "scope" => $scope], @$options["headers"] ?? []);
    }
}