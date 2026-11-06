# AppCelerator PHP SDK

The official PHP library for the AppCelerator APIs. Use it to call the AppCelerator services (OAuth2, Countries, Files, ...) from your backend: the SDK requests, caches and renews an access token per service for you, calls the predefined endpoints of a service, and sends any other request to a service with its token.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Calling any endpoint](#calling-any-endpoint)
- [Acting for a user or a tenant](#acting-for-a-user-or-a-tenant)
- [Tokens](#tokens)
- [Authentication](#authentication)
- [Resources](#resources)
- [Responses](#responses)
- [Error handling](#error-handling)
- [Upgrading from 1.x](#upgrading-from-1x)
- [License](#license)

## Requirements

- PHP 8.3 or later
- The `curl` and `json` extensions
- Optional: the `apcu` extension, to share cached tokens between php-fpm workers

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
use AppCelerator\Config;
use AppCelerator\Auth\Credentials;

$client = new Client(new Config(
    tokenUrl:    'https://myapp.oauth2.eu.appcelerator.io/token',
    credentials: Credentials::of('<client-id>', '<client-secret>'),
    services: [
        'oauth2'    => 'https://myapp.oauth2.eu.appcelerator.io',
        'countries' => 'https://myapp.countries.eu.appcelerator.io',
    ],
));

// Predefined endpoints
$user    = $client->oauth2->users->get('user_Q7zCI6ZMjQWFojQP5lCPAXF8s5AxRvY');
$country = $client->countries->countries->get('nl');

// Any endpoint of a service
$response = $client->countries->request('GET', '/country/nl/', ['query' => ['fields' => 'name']]);
$response->json();
```

Every service gets its own token, requested the first time it is used, cached and renewed before it expires.

## Configuration

```php
$client = new Client(new Config(
    tokenUrl:       'https://myapp.oauth2.eu.appcelerator.io/token',
    credentials:    Credentials::fromEnv('APC_CLIENT_ID', 'APC_CLIENT_SECRET'),
    services: [
        'oauth2'    => 'https://myapp.oauth2.eu.appcelerator.io',
        'countries' => ['url' => 'https://myapp.countries.eu.appcelerator.io', 'scope' => 'countries.country:read'],
        'billing'   => 'https://myapp.billing.eu.appcelerator.io', // no predefined endpoints: request() only
    ],
    headers:        ['identifier' => 'myapp.dev'],  // sent with every request
    tokenStore:     null,                           // where tokens are cached, see Tokens
    tokenCacheFile: null,
    timeout:        30,                              // seconds per request
));
```

| Argument | Description |
|---|---|
| `tokenUrl` | The token endpoint of your OAuth2 service |
| `credentials` | Your client: `Credentials::of($clientId, $clientSecret)` or `Credentials::fromEnv($clientIdVariable, $clientSecretVariable)` |
| `services` | Per service its URL, or its settings (below). The name is how you reach it: `$client->countries`, `$client->service('billing')` |
| `headers` | Headers sent with every request |
| `tokenStore` / `tokenCacheFile` | Where tokens are cached, see [Tokens](#tokens) |
| `timeout` | Seconds per request |

Settings per service:

| Setting | Default | Description |
|---|---|---|
| `url` | required | The base URL of the service |
| `urls` | none | Other base URLs of the service that may receive its token, e.g. an internal or test URL |
| `resource` | `api://<name>-service` | The resourceURI of the service at the OAuth2 service |
| `scope` | `<resource>/.default` | The scopes requested for its token. The default returns every scope your client holds on that service |
| `hosts` | `<name>.*.appcelerator.io`, `*.<name>.*.appcelerator.io`, `*.<name>.*.*.appcelerator.io` | Other hosts of the service (regional and tenant URLs) that may receive its token. `*` is one DNS label, https only |

### From environment variables

`Config::fromEnv()` reads the usual variables of the platform:

| Variable | Used as |
|---|---|
| `APC_AUTH_TOKEN_URI` | `tokenUrl` (otherwise `OAUTH2_SERVICE_URI` + `/token`) |
| `APC_CLIENT_ID`, `APC_CLIENT_SECRET` | `credentials` |
| `<NAME>_SERVICE_URI` | a service per variable: `OAUTH2_SERVICE_URI` is `oauth2`, `FILES_SERVICE_URI` is `files` |
| `RESOURCE_DOMAIN_IDENTIFIER` | the `identifier` header |

```php
$client = new Client(Config::fromEnv(['countries' => ['scope' => 'countries.country:read']]));
```

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

Every method of a predefined endpoint accepts an `$options` array as its last argument. Use `headers` to add or override request headers, for example to call an endpoint with the token of a user instead of the token of the client:

```php
$profile = $client->oauth2->users->self(options: [
    'headers' => ['Authorization' => 'Bearer ' . $userAccessToken],
]);
```

## Calling any endpoint

`request()` calls any endpoint of a service, also one without predefined endpoints, with the token of that service:

```php
$response = $client->countries->request('GET', '/country/nl/');
$response = $client->billing->request('POST', '/invoice/', ['json' => ['amount' => 100]]);
$response = $client->service('billing')->request('DELETE', "/invoice/$invoiceId/");

// An absolute URL of the service, e.g. another region or tenant of it
$response = $client->oauth2->request('POST', 'https://other.oauth2.us.appcelerator.io/service-domain/', ['form' => [...]]);

// Or let the client find the service of a URL
$response = $client->request('GET', 'https://myapp.files.eu.appcelerator.io/file/abc/');
```

A URL that is not of a configured service is refused, so a token is never sent anywhere else.

A service whose URL is only known at runtime, such as one per cluster, is added with `withService()`. It returns a new client that shares the token cache, so every cluster gets the same token for that resource:

```php
$cluster = $client->withService('kubernetes-client', ['url' => $clientApiUrl, 'resource' => 'api://kubernetes-client']);

$cluster->service('kubernetes-client')->request('GET', '/pods/');
```

| Option | Description |
|---|---|
| `query` | Query parameters |
| `json` | A body sent as JSON |
| `form` | A body sent as `application/x-www-form-urlencoded` |
| `multipart` | Multipart fields, a value may be a `CURLFile` |
| `body` | A raw body (string); set its `Content-Type` in `headers` |
| `headers` | Headers on top of those of the client |
| `token` | A token of your own instead of the one of the client |
| `auth` | `false`: send no token |
| `timeout` | Seconds |
| `retry` | `false`: no second attempt when the service rejects the token |
| `throw` | `true`: an unsuccessful response throws `AppCeleratorHttpException` |

`request()` returns the response whatever its status, unless `throw` is set:

```php
$response->status();          // 200
$response->isSuccessful();
$response->json();            // the decoded JSON body
$response->getParameter('data');
$response->body();            // the raw body
$response->header('content-type');
```

## Acting for a user or a tenant

A backend that serves signed-in users (e.g. a backend for frontend) calls the services as that user: the token of the user is exchanged for a token of the same user per service (token exchange, RFC 8693). Your client needs delegated permissions for those scopes.

```php
$user = $client->onBehalfOf($userAccessToken, $sessionStore);

$user->files->request('GET', '/file/');
$user->oauth2->users->self();
```

`$sessionStore` is a `TokenStore` for the tokens of that user, e.g. in their session. Without it they are kept in memory for the current process. The tokens of users never go into the shared store of the client.

In a tenant, the user acts with the roles they hold for that tenant. The token is of the tenant and the `identifier` header of the requests is the tenant:

```php
$tenant = $client->onBehalfOf($userAccessToken, $sessionStore)->forTenant('customer.dev');

$tenant->oauth2->roles->list();
```

`onBehalfOf()` and `forTenant()` return a new client, the original one is unchanged.

## Tokens

The client requests a token per service with the client credentials grant and `scope` of the service, caches it and renews it 60 seconds before it expires. When the service rejects the token (401, e.g. signed with a key that was rotated), the client drops it, requests a new one and sends the request once more.

Tokens are cached in this process and in the token store:

| Store | When |
|---|---|
| `ApcuTokenStore` | Default when APCu is enabled (php-fpm): shared by the workers |
| `FileTokenStore` | Default otherwise (CLI, development): a JSON file in the temp directory, readable by its owner only. `tokenCacheFile` sets its path |
| `MemoryTokenStore` | This process only, e.g. tests |
| Your own | Implement `AppCelerator\Auth\Store\TokenStore`, e.g. Redis or a session |

One process requests a token, the others wait for it. Drop every cached token, e.g. after your OAuth2 service was installed again:

```php
$client->tokens()->forgetAll();
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
| `scopes` | `get`, `list`, `create`, `update`, `delete`, `subject`, `assignments`, `assign`, `revoke`, `approve`, `reject` |
| `roles` | `get`, `list`, `create`, `update`, `delete`, `assignments`, `assign`, `revoke`, `approve`, `reject` |
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
$client->oauth2->scopes->assign($clientId, 'invoice:read', kind: 'application'); // only for the client's own tokens
$client->oauth2->roles->assign($userId, 'accountant');      // role to user

$client->oauth2->roles->revoke($userId, 'accountant');

// Every scope of a subject, directly and through roles
$scopes = $client->oauth2->scopes->subject($userId);

// Who holds a role or received a scope, with filters
$holders = $client->oauth2->roles->assignments('accountant', ['subjectId' => $userId]);
$receivers = $client->oauth2->scopes->assignments('invoice:read', ['kind' => 'delegated']);
```

A scope or role is addressed by its ID or its value (values never contain an underscore, IDs always do). The assignments are a sub-resource: `/role/{roleIdOrValue}/assignment/` and `/scope/{scopeIdOrValue}/assignment/`, see the OAuth2 service API docs.

A role can be assigned **for a tenant**: the user then acts in that tenant through a token exchange with `tenant`, with the scopes of that role. Only the owner of the role assigns it for a tenant, to its own users. `revoke`, `approve` and `reject` take the same `tenant`:

```php
$client->oauth2->roles->assign($userId, 'oauth2-federated-access-scopes', tenant: 'myapp.dev');
$client->oauth2->roles->revoke($userId, 'oauth2-federated-access-scopes', tenant: 'myapp.dev');

// Who holds the role for the tenant
$holders = $client->oauth2->roles->assignments('oauth2-federated-access-scopes', ['tenant' => 'myapp.dev']);
```

An assignment can be **pending**, for example when it is requested from another tenant. A pending assignment gives no access until the owner of the scope or role approves it:

```php
$client->oauth2->roles->approve($userId, 'accountant');
$client->oauth2->roles->reject($userId, 'accountant');

$client->oauth2->scopes->approve($userId, 'invoice:read');
$client->oauth2->scopes->reject($userId, 'invoice:read');

// The pending assignments
$pending = $client->oauth2->roles->assignments('accountant', ['permissionGranted' => 'null']);
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

A predefined endpoint that fails throws an `AppCelerator\Exceptions\AppCeleratorHttpException`, `request()` does with the `throw` option. A token request that fails always throws, `getOAuthError()` tells why:

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

## Upgrading from 1.x

A client with a fixed token, or a function that returns a token per service, still works as in 1.x:

```php
$client = new Client($accessToken, ['oauth2' => 'https://myapp.oauth2.eu.appcelerator.io']);
$client = new Client(fn(string $service) => $tokens->get("api://$service-service"), $serviceUrls);
```

New in 2.0:

- `Config`: the client requests, caches and renews a token per service itself, so you no longer request tokens and build a client per token.
- `request()` on every service, and on the client for an absolute URL. Configured services without predefined endpoints (`$client->billing`, `$client->service('billing')`).
- `onBehalfOf()` and `forTenant()`: tokens of a user and of a tenant, through token exchange.
- A rejected token is replaced once, also for predefined endpoints.
- Role and scope assignments are a sub-resource: `/role/{roleIdOrValue}/assignment/` and `/scope/{scopeIdOrValue}/assignment/`. `roles->assignments()` and `scopes->assignments()` list them, `assign`, `revoke`, `approve` and `reject` keep their arguments. This needs an OAuth2 service with these endpoints.
- Service URLs keep their case (1.x lowercased them).

New in 2.1:

- `withService()`: a service added at runtime, e.g. a URL per cluster, sharing the token cache.
- `urls` per service: more base URLs that may receive its token.
- `<name>.*.appcelerator.io` (a region without tenant) is one of the default hosts of a service.
- `Response::getParameter()`.

## License

The AppCelerator PHP SDK is released under the [MIT License](https://opensource.org/licenses/MIT).
