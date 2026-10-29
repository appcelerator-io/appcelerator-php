<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * RolesController
 */
class RolesController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "role/", $opts);
    }

    /**
     * @param string $value Alphanumeric role value
     * @param array $params name, description
     * @param array $options
     */
    public function create($value, $params = [], array $options = [])
    {
        return self::curl("POST", "", array_merge(["value" => $value], $params), @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $role Role ID or name
     * @param array $options
     */
    public function assign($subjectId, $role, array $options = [])
    {
        return self::curl("POST", "assign", ["subjectId" => $subjectId, "role" => $role], @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $role Role ID or name
     * @param array $options
     */
    public function revoke($subjectId, $role, array $options = [])
    {
        return self::curl("POST", "revoke", ["subjectId" => $subjectId, "role" => $role], @$options["headers"] ?? []);
    }

    /**
     * Approves a pending role assignment of subject, only the owner of the role can decide
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     */
    public function approve($subjectId, $role, array $options = [])
    {
        return self::curl("POST", "approve", self::assignmentParams($subjectId, $role), @$options["headers"] ?? []);
    }

    /**
     * Rejects a pending role assignment of subject, only the owner of the role can decide
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     */
    public function reject($subjectId, $role, array $options = [])
    {
        return self::curl("POST", "reject", self::assignmentParams($subjectId, $role), @$options["headers"] ?? []);
    }

    /**
     * approve and reject take the role as roleId or value
     */
    private static function assignmentParams($subjectId, $role) : array
    {
        return ["subjectId" => $subjectId, (preg_match("/^role_[0-9a-zA-Z]{12,24}$/", (string) $role) ? "roleId" : "value") => $role];
    }
}
