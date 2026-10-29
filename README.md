# AppCelerator PHP SDK

The official PHP library for the AppCelerator APIs. Use it to request access tokens and to manage the users, clients, scopes, roles and identity providers of your AppCelerator OAuth2 service.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Resources](#resources)
- [Responses](#responses)
- [Error handling](#error-handling)
- [License](#license)

## Requirements

- PHP 8.3 or later
- The `curl` and `json` extensions

The SDK has no other dependencies.

## Installation

Install the SDK with [Composer](https://getcomposer.org/):

```bash
composer require appcelerator/appcelerator-php
```

Then load Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Quick start

```php
use AppCelerator\Client;

$oauth2Url = 'https://myapp.oauth2.eu.appcelerator.io';

// 1. Request an access token for your client
$tokenClient = new Client('', ['oauth2' => $oauth2Url]);

$token = $tokenClient->oauth2->token->clientCredentials('<client-id>', '<client-secret>');

// 2. Call the API with the access token
$client = new Client($token['access_token'], ['oauth2' => $oauth2Url]);

$user = $client->oauth2->users->get('user_Q7zCI6ZMjQWFojQP5lCPAXF8s5AxRvY');

echo $user['email'];
```

## Configuration

```php
$client = new Client(string $accessToken, array $serviceUrls);
```

| Argument | Description |
|---|---|
| `$accessToken` | Sent as `Authorization: Bearer <token>` on every request. Pass an empty string for requests that do not need a token, such as token requests. |
| `$serviceUrls` | The base URL per service, e.g. `['oauth2' => 'https://myapp.oauth2.eu.appcelerator.io']`. |

### Service URLs

Every service of your tenant has its own URL:

```
https://<tenant>.<service>.<region>
```

| Part | Description | Example |
|---|---|---|
| `tenant` | The domain of your tenant | `myapp` |
| `service` | The name of the service | `oauth2` |
| `region` | The region your tenant is hosted in | `eu.appcelerator.io` |

For example, the OAuth2 service of tenant `myapp` in the EU region is `https://myapp.oauth2.eu.appcelerator.io`.

### Per-request options

Every method accepts an `$options` array as its last argument. Use `headers` to add or override request headers, for example to call an endpoint with the token of a user instead of the token of the client:

```php
$profile = $client->oauth2->users->self(options: [
    'headers' => ['Authorization' => 'Bearer ' . $userAccessToken],
]);
```

## Authentication

Tokens are requested at the token endpoint through `$client->oauth2->token`, with one method per grant type. A successful request returns:

```php
[
    'access_token'  => 'eyJhbGciOi...',
    'token_type'    => 'Bearer',
    'expires_in'    => 3600,
    'expires'       => 1793221598,
    'scope'         => 'openid profile email',
    'subject'       => 'user_Q7zCI6ZMjQWFojQP5lCPAXF8s5AxRvY',
    'refresh_token' => '...',            // when offline_access is requested
    'id_token'      => 'eyJhbGciOi...',  // when openid is requested
]
```

A client can only use the grant types that are enabled for it.

### Client credentials

Machine-to-machine access, for backend services:

```php
$token = $client->oauth2->token->clientCredentials($clientId, $clientSecret);
```

### Authorization code with PKCE

The recommended flow for web, mobile and single-page applications that sign in users.

1. Create a code verifier and its code challenge.
2. Send the user to the `/authorize` endpoint of your OAuth2 service with the code challenge.
3. The user signs in and is redirected to your redirect URI with an authorization code.
4. Exchange the code for a token:

```php
// Step 1, keep the verifier for step 4 (e.g. in the session)
$codeVerifier  = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

// Step 4
$token = $client->oauth2->token->authorizationCode($clientId, $code, $codeVerifier, $redirectUri);
```

### Refresh token

Request the `offline_access` scope to receive a refresh token. Every refresh returns a new refresh token that replaces the one you used, so always store the new one.

```php
$token = $client->oauth2->token->refreshToken($clientId, $refreshToken);
```

### Password and multi-factor authentication

The password grant signs in a user with an email address and password. It is only meant for trusted first-party applications; prefer the authorization code flow.

```php
$result = $client->oauth2->token->password($clientId, $email, $password, 'openid profile email');

// With MFA enabled the user receives a one-time code instead of a token
if (($result['flow'] ?? null) === 'mfa') {
    $result = $client->oauth2->token->mfa($clientId, $result['mfa_token'], $otp);
}
```

### Identity provider sign-in

Exchange the `id_token` of an identity provider that is enabled for your tenant, for example from Sign in with Google:

```php
$token = $client->oauth2->token->jwtBearer($clientId, $idToken);
```

### Scopes

The `scope` argument is a **space-delimited** list. When you omit it, all scopes granted to the client and user are requested.

| Scope | Meaning |
|---|---|
| `invoice:read` | A scope of your tenant |
| `scope://<client-id>/invoice:read` | A scope of another client (resource); the token's audience is set to that client |
| `scope://<client-id>/.default` | All granted scopes of that client |
| `openid` `profile` `email` | Return an `id_token` and allow the user info endpoint |
| `offline_access` | Return a refresh token |

All scopes in one request must address the same target.

### Verifying tokens

The signing keys and the OpenID Connect configuration are public:

```php
$configuration = $client->oauth2->openIDConnect->discovery();
$keys          = $client->oauth2->openIDConnect->jwks();
```

## Resources

All OAuth2 resources are available on `$client->oauth2`.

| Property | Methods |
|---|---|
| `token` | `clientCredentials`, `authorizationCode`, `refreshToken`, `password`, `mfa`, `jwtBearer`, `request` |
| `users` | `get`, `list`, `create`, `update`, `delete`, `self`, `authenticate`, `changeEmail`, `confirmEmail`, `changePassword`, `requestPasswordReset`, `resetPassword`, `logs` |
| `clients` | `get`, `list`, `create`, `update`, `delete`, `self`, `logs` |
| `secrets` | `get`, `list`, `create`, `update`, `delete` |
| `scopes` | `get`, `list`, `create`, `update`, `delete`, `subject`, `assign`, `revoke`, `approve`, `reject` |
| `roles` | `get`, `list`, `create`, `update`, `delete`, `assign`, `revoke`, `approve`, `reject` |
| `identityProviders` | `get`, `list`, `create`, `update`, `delete` |
| `userIdentities` | `get`, `list`, `create`, `delete` |
| `verification` | `start`, `confirm` |
| `openIDConnect` | `discovery`, `jwks`, `userInfo` |
| `authorizationCodes` | `get` |

### Users

```php
$users = $client->oauth2->users;

$created = $users->create('jane@example.com', $password, ['name' => 'Jane Doe']);
$user    = $users->get($userId);
$users->update($userId, ['givenName' => 'Jane']);
$users->delete($userId);

// Password reset
$reset = $users->requestPasswordReset('jane@example.com');
$users->resetPassword($reset['identifier'], $reset['token'], $newPassword);

// Audit log
$entries = $users->logs($userId, ['limit' => 50]);
```

### Clients and secrets

```php
$app = $client->oauth2->clients->create('my-web-app', 'authorization_code,refresh_token', 'https://example.com/callback');

// Only a hash is stored: the secret value is returned once, store it right away
$secret = $client->oauth2->secrets->create($clientId, ['name' => 'production']);

$secret['secretId'];
$secret['secret'];   // the client secret, use it with token->clientCredentials()

$client->oauth2->secrets->delete($clientId, $secretId);
```

### Scopes and roles

Scopes are permissions such as `invoice:read`. Roles group scopes. Both are assigned to a subject: a user, a client or a role.

```php
$client->oauth2->scopes->create('invoice:read', ['description' => 'Read invoices']);
$client->oauth2->roles->create('accountant', ['description' => 'Finance team']);

$client->oauth2->scopes->assign($roleId, 'invoice:read');   // scope to role
$client->oauth2->roles->assign($userId, 'accountant');      // role to user

$client->oauth2->roles->revoke($userId, 'accountant');

// Every scope of a subject, directly and through roles
$scopes = $client->oauth2->scopes->subject($userId);
```

An assignment can be **pending**, for example when it is requested from another tenant. A pending assignment gives no access until the owner of the scope or role approves it:

```php
$client->oauth2->roles->approve($userId, 'accountant');
$client->oauth2->roles->reject($userId, 'accountant');

$client->oauth2->scopes->approve($scopeId, $userId);
$client->oauth2->scopes->reject($scopeId, $userId);
```

### Identity providers

Let users sign in with an external OpenID Connect provider:

```php
$client->oauth2->identityProviders->create('Google', 'https://accounts.google.com', $googleClientId, [
    'clientSecret' => $googleClientSecret,
    'createUsers'  => true,
    'enabled'      => true,
]);

// Link an existing user to an identity at the provider
$client->oauth2->userIdentities->create($userId, 'https://accounts.google.com', $googleSubject);
```

### Step-up verification

Ask a signed-in user to confirm a sensitive action with a one-time code. Both calls use the user's access token:

```php
$options = ['headers' => ['Authorization' => 'Bearer ' . $userAccessToken]];

$verification = $client->oauth2->verification->start('account:delete', $options);

// The user receives a one-time code
$token = $client->oauth2->verification->confirm($verification['mfa_token'], $otp, $options);
```

The returned token is limited to the action the verification was started for.

### Lists

`list`, `logs` and `subject` accept the following parameters:

| Parameter | Description |
|---|---|
| `search` | Search text (supported per resource) |
| `fields` | Comma-separated fields to return |
| `orderBy` | Field to sort on, default `created` |
| `orderByOrder` | `asc` or `desc`, default `desc` |
| `limit` | Results per page, 0–50, default 25 |
| `offset` / `page` | Position in the result set |

```php
$page = $client->oauth2->users->list(['search' => 'jane', 'limit' => 10, 'page' => 2]);
```

## Responses

Methods return the decoded JSON response as an array. `get`, `list`, `logs` and `subject` return the `data` element of the response directly.

The HTTP response of the last call is available on the resource object, including the status code and headers. Keep a reference to the resource, because every access to `$client->oauth2->users` creates a new object:

```php
$users = $client->oauth2->users;
$users->get($userId);

$response = $users->getResponse();
$response->getStatusCode();
$response->getHeader('Content-Type');
```

## Error handling

A request that fails throws an `AppCelerator\Exceptions\AppCeleratorHttpException`:

```php
use AppCelerator\Exceptions\AppCeleratorHttpException;

try {
    $token = $client->oauth2->token->clientCredentials($clientId, $clientSecret);
} catch (AppCeleratorHttpException $e) {
    $e->getStatusCode();        // 401
    $e->getErrorCode();         // 'credentialsInvalid'
    $e->getErrorDescription();  // 'Client authentication failed'
    $e->getOAuthError();        // 'invalid_client' (token requests only)
    $e->getErrorData();         // the complete error response
}
```

| Method | Description |
|---|---|
| `getStatusCode()` | HTTP status code |
| `getErrorCode()` | Error code of the API, such as `tokenInvalid`, `credentialsInvalid` or `scopeInsufficient`. Use this to handle specific errors. |
| `getErrorDescription()` | Human-readable description |
| `getOAuthError()` | Standard OAuth2 error of a token request (`invalid_client`, `invalid_grant`, `unauthorized_client`, `invalid_scope`, ...), `null` for other requests |
| `getErrorData()` | The complete decoded error response |
| `getResponse()` | The HTTP response |

A response that is not JSON, such as an HTML error page from a proxy, also throws `AppCeleratorHttpException`. A request that cannot reach the server at all throws a regular `Exception` with the curl error.

## License

The AppCelerator PHP SDK is released under the [MIT License](https://opensource.org/licenses/MIT).
