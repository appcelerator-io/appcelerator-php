<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * UserIdentitiesController
 *  Links a user to the subject of an identity provider. A user identity cannot be updated, delete and create it instead.
 */
class UserIdentitiesController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "user-identity/", $opts);
    }

    /**
     * @param string $userIdentityId ID of user identity
     * @param array $params fields
     * @param array $options
     */
    public function get($userIdentityId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$userIdentityId/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param array $params userId, issuer, subject, fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     */
    public function list(array $params = [], array $options = [])
    {
        return self::curl("GET", "", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $userId ID of user
     * @param string $issuer Issuer (iss) of the identity provider e.g. https://accounts.google.com
     * @param string $subject Subject (sub) that identifies the user at the identity provider
     * @param array $options
     */
    public function create($userId, $issuer, $subject, array $options = [])
    {
        return self::curl("POST", "", ["userId" => $userId, "issuer" => $issuer, "subject" => $subject], @$options["headers"] ?? []);
    }

    /**
     * @param string $userIdentityId ID of user identity
     * @param array $options
     */
    public function delete($userIdentityId, array $options = [])
    {
        return self::curl("DELETE", "$userIdentityId/", [], @$options["headers"] ?? []);
    }
}
