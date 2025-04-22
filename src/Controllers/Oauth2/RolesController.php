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
}