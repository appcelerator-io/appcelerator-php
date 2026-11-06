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
     * Who holds the role: its assignments, also pending (permissionGranted null) and rejected (false) ones
     *
     * @param string $role Role ID or value
     * @param array $params subjectId, tenant (the tenant the assignment counts for, the own identifier for those of the owner itself), permissionGranted <true,false,null,all>, fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     * @return array [["subjectId", "roleId", "permissionGranted", "targetOwnerId" (the tenant, "" for the owner itself), "created"], ...]
     */
    public function assignments($role, array $params = [], array $options = [])
    {
        return self::curl("GET", "$role/assignment/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function assign($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("POST", "$role/assignment/", self::withTenant(["subjectId" => $subjectId], $tenant), @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $role Role ID or value
     * @param array $options
     * @param null|string $tenant The tenant (identifier) the subject holds the role for, the subject acts in that tenant through a token exchange with tenant. Only the owner of the role assigns it for a tenant, to its own users. Null for the owner itself
     */
    public function revoke($subjectId, $role, array $options = [], null|string $tenant = null)
    {
        return self::curl("DELETE", "$role/assignment/$subjectId/", self::withTenant([], $tenant), @$options["headers"] ?? []);
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
        return self::curl("POST", "$role/assignment/$subjectId/approve", self::withTenant([], $tenant), @$options["headers"] ?? []);
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
        return self::curl("POST", "$role/assignment/$subjectId/reject", self::withTenant([], $tenant), @$options["headers"] ?? []);
    }

    /**
     * Adds the tenant, when given
     */
    private static function withTenant(array $params, null|string $tenant) : array
    {
        return $tenant !== null ? $params + ["tenant" => $tenant] : $params;
    }
}
