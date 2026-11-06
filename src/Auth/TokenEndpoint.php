<?php
declare(strict_types=1);

namespace AppCelerator\Auth;

/**
 * TokenEndpoint
 *  Requests tokens at the OAuth2 service. A token is returned as ["token" => JWT, "expires" => unix time].
 */
interface TokenEndpoint
{
    /**
     * clientCredentials
     *  A token of the client itself for $scope, e.g. "api://files-service/.default"
     */
    public function clientCredentials(string $scope) : array;

    /**
     * tokenExchange
     *  RFC 8693: the token of a user ($subjectToken) for a token of the same user for $scope (on behalf of the user). With
     *  $tenant the user acts in that tenant: the token is of the tenant, with the scopes of the roles the user holds there.
     */
    public function tokenExchange(#[\SensitiveParameter] string $subjectToken, string $scope, null|string $tenant = null) : array;
}
