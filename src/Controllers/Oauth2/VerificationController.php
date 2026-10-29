<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * VerificationController
 *  Step-up verification of the logged-in user, the user of the access token (use options['headers']['Authorization'])
 */
class VerificationController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "verification", $opts);
    }

    /**
     * Sends a one-time code to the user
     * @param null|string $action Limits the confirmed token to this action e.g. change-email or account:delete
     * @param array $options
     * @return array [mfa_token, expires_in]
     */
    public function start($action = null, array $options = [])
    {
        return self::curl("POST", "", $action !== null ? ["action" => $action] : [], @$options["headers"] ?? []);
    }

    /**
     * Confirms the one-time code and returns a new access token of the user
     * @param string $mfaToken mfa_token returned by start
     * @param string $otp One-time code received by the user
     * @param array $options
     * @return array [tokenData]
     */
    public function confirm($mfaToken, $otp, array $options = [])
    {
        return self::curl("POST", "confirm", ["mfa_token" => $mfaToken, "otp" => $otp], @$options["headers"] ?? []);
    }
}
