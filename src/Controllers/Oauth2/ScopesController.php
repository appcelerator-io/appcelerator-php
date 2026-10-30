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
     * Approves a pending scope assignment of subject, only the owner of the scope can decide
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     */
    public function approve($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "approve", self::assignmentParams($subjectId, $scope), @$options["headers"] ?? []);
    }

    /**
     * Rejects a pending scope assignment of subject, only the owner of the scope can decide
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     */
    public function reject($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "reject", self::assignmentParams($subjectId, $scope), @$options["headers"] ?? []);
    }

    /**
     * approve and reject take the scope as scopeId or value
     */
    private static function assignmentParams($subjectId, $scope) : array
    {
        return ["subjectId" => $subjectId, (preg_match("/^scope_[0-9a-zA-Z]{12,24}$/", (string) $scope) ? "scopeId" : "value") => $scope];
    }
}
