<?php
declare(strict_types=1);

namespace AppCelerator\Auth;

use AppCelerator\Exceptions\AppCeleratorException;

/**
 * Credentials
 *  The client (application) the SDK requests tokens for: its clientId and secret at the OAuth2 service
 */
final class Credentials
{
    public function __construct(public readonly string $clientId, #[\SensitiveParameter] private readonly string $clientSecret)
    {
        if($clientId === "" || $clientSecret === "")
            throw new AppCeleratorException("Client credentials are missing, set a clientId and clientSecret");
    }

    /**
     * of
     */
    public static function of(string $clientId, #[\SensitiveParameter] string $clientSecret) : self
    {
        return new self($clientId, $clientSecret);
    }

    /**
     * fromEnv
     *  Reads the credentials from environment variables, e.g. APC_CLIENT_ID and APC_CLIENT_SECRET
     */
    public static function fromEnv(string $clientIdVariable = "APC_CLIENT_ID", string $clientSecretVariable = "APC_CLIENT_SECRET") : self
    {
        return new self(Env::get($clientIdVariable) ?? "", Env::get($clientSecretVariable) ?? "");
    }

    /**
     * secret
     */
    public function secret() : string
    {
        return $this->clientSecret;
    }

    /**
     * __debugInfo
     *  Never print the secret
     */
    public function __debugInfo() : array
    {
        return ["clientId" => $this->clientId, "clientSecret" => "***"];
    }
}
