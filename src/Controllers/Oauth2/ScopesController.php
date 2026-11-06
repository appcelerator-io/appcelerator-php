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
     * Who received the scope: its assignments to users, clients and roles, also pending (permissionGranted null) and
     * rejected (false) ones. Users that hold the scope through a role are listed under the role (roles->assignments)
     *
     * @param string $scope Scope ID or value
     * @param array $params subjectId, kind <application,delegated,both>, permissionGranted <true,false,null,all>, fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     * @return array [["subjectId", "scopeId", "permissionGranted", "kind", "created"], ...]
     */
    public function assignments($scope, array $params = [], array $options = [])
    {
        return self::curl("GET", "$scope/assignment/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     * @param null|string $kind For a client: application (its own client_credentials tokens), delegated (tokens of a user it signs in) or both (default)
     */
    public function assign($subjectId, $scope, array $options = [], null|string $kind = null)
    {
        return self::curl("POST", "$scope/assignment/", array_filter(["subjectId" => $subjectId, "kind" => $kind], fn($value) => $value !== null), @$options["headers"] ?? []);
    }

    /**
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     */
    public function revoke($subjectId, $scope, array $options = [])
    {
        return self::curl("DELETE", "$scope/assignment/$subjectId/", [], @$options["headers"] ?? []);
    }

    /**
     * Approves a pending scope assignment of subject, only the owner of the scope can decide
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     */
    public function approve($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "$scope/assignment/$subjectId/approve", [], @$options["headers"] ?? []);
    }

    /**
     * Rejects a pending scope assignment of subject, only the owner of the scope can decide
     * @param string $subjectId ID of subject
     * @param string $scope Scope ID or value
     * @param array $options
     */
    public function reject($subjectId, $scope, array $options = [])
    {
        return self::curl("POST", "$scope/assignment/$subjectId/reject", [], @$options["headers"] ?? []);
    }
}
