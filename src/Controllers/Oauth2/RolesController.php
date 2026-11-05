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
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function assign($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("POST", "assign", self::withTenant(["subjectId" => $subjectId, "role" => $role], $tenant), @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $role Role ID or name
     * @param array $options
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function revoke($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("POST", "revoke", self::withTenant(["subjectId" => $subjectId, "role" => $role], $tenant), @$options["headers"] ?? []);
    }

    /**
     * Approves a pending role assignment of subject, only the owner of the role can decide
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function approve($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("POST", "approve", self::withTenant(self::assignmentParams($subjectId, $role), $tenant), @$options["headers"] ?? []);
    }

    /**
     * Rejects a pending role assignment of subject, only the owner of the role can decide
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function reject($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("POST", "reject", self::withTenant(self::assignmentParams($subjectId, $role), $tenant), @$options["headers"] ?? []);
    }

    /**
     * approve and reject take the role as roleId or value
     */
    private static function assignmentParams($subjectId, $role) : array
    {
        return ["subjectId" => $subjectId, (preg_match("/^role_[0-9a-zA-Z]{12,24}$/", (string) $role) ? "roleId" : "value") => $role];
    }

    /**
     * Adds the tenant, when given
     */
    private static function withTenant(array $params, null|string $tenant) : array
    {
        return $tenant !== null ? $params + ["tenant" => $tenant] : $params;
    }
}
