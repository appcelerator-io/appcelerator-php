<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * UsersController
 */
class UsersController extends CRUDController
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "user/", $opts);
    }

    /**
     * Retrieve user data based on the provided token
     * Use options['headers']['Authorization'] to overwrite the default token
     * @param array $params - fields
     */
    public function self(array $params = [], array $options = [])
    {
        return self::curl("GET", "self", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param string $password User password
     * @param array $params name, givenName, familyName
     * @param array $options
     */
    public function create($email, $password, $params = [], array $options = [])
    {
        return self::curl("POST", "", array_merge(["email" => $email, "password" => $password], $params), @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param string $password User password
     * @param array $params scope (space delimited, all granted scopes when omitted)
     * @param array $options
     * @return array [tokenData] or [flow, mfa_token] when MFA is enabled
     */
    public function authenticate($email, $password, array $params = [], array $options = [])
    {
        $params = array_merge([
            "email" => $email,
            "password" => $password,
        ], $params);

        return self::curl("POST", "authenticate", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID
     * @param string $email User email
     * @param array $options
     * @return array [emailConfirmToken]
     */
    public function changeEmail($userId, $email, array $options = [])
    {
        return self::curl("POST", "$userId/change-email", ["email" => $email], @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID
     * @param string $emailConfirmToken User emailConfirmToken
     * @param array $options
     * @return array [code]
     */
    public function confirmEmail($userId, $emailConfirmToken = "", array $options = [])
    {
        return self::curl("POST", "$userId/confirm-email", ["emailConfirmToken" => $emailConfirmToken], @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID
     * @param string $password User password
     * @param string $passwordRepeat Repeat password
     * @param string $currentPassword Current password
     * @param array $options
     * @return array [code]
     */
    public function changePassword($userId, $password, $passwordRepeat, $currentPassword, array $options = [])
    {
        return self::curl("POST", "$userId/change-password", ["password" => $password, "passwordRepeat" => $passwordRepeat, "currentPassword" => $currentPassword], @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param array $options
     * @return array [userId,identifier,token]
     */
    public function requestPasswordReset($email, array $options = [])
    {
        return self::curl("POST", "request-password-reset", ["email" => $email], @$options["headers"] ?? []);
    }

    /**
     * @param string $identifier Identifier received from requestPasswordReset
     * @param string $token Token received from requestPasswordReset
     * @param string $password New password
     * @param null|string $passwordRepeat Repeat new password
     * @param array $options
     */
    public function resetPassword($identifier, $token, $password, $passwordRepeat = null, array $options = [])
    {
        $params = ["identifier" => $identifier, "token" => $token, "password" => $password];

        if($passwordRepeat !== null)
            $params["passwordRepeat"] = $passwordRepeat;

        return self::curl("POST", "reset-password", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID
     * @param array $params fields, orderBy, orderByOrder <asc,desc>, limit, offset, page
     * @param array $options
     * @return array
     */
    public function logs($userId, array $params = [], array $options = [])
    {
        return self::curl("GET", "$userId/log/", $params, @$options["headers"] ?? [])["data"];
    }
}
