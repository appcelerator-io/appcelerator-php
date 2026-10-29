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
     * @param string $value Scope value e.g. invoice:read
     * @param array $params name, description
     * @param array $options
     */
    public function create($value, $params = [], array $options = [])
    {
        return self::curl("POST", "", array_merge(["value" => $value], $params), @$options["headers"] ?? []);
    }

    /**
     * Returns every scope assigned to subject with ID, directly or through a role
     *
     * @param string $subjectId ID of subject
     * @param array $params fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     */
    public function subject($subjectId, array $params = [], array $options = [])
    {
        return self::curl("GET", "subject/$subjectId", $params, @$options["headers"] ?? [])["data"];
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

    /**
     * Grants the permission of a scope assigned to subject
     * @param string $scopeId ID of scope
     * @param string $subjectId ID of subject
     * @param array $options
     */
    public function approve($scopeId, $subjectId, array $options = [])
    {
        return self::curl("POST", "$scopeId/approve", ["subjectId" => $subjectId], @$options["headers"] ?? []);
    }

    /**
     * Rejects the permission of a scope assigned to subject
     * @param string $scopeId ID of scope
     * @param string $subjectId ID of subject
     * @param array $options
     */
    public function reject($scopeId, $subjectId, array $options = [])
    {
        return self::curl("POST", "$scopeId/reject", ["subjectId" => $subjectId], @$options["headers"] ?? []);
    }
}
