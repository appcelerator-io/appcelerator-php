<?php

require __DIR__ . '/src/Library.php';

// Interfaces
require __DIR__ . '/src/Interfaces/ControllerInterface.php';
require __DIR__ . '/src/Interfaces/ServiceInterface.php';

// Exceptions
require __DIR__ . '/src/Exceptions/AppCeleratorException.php';
require __DIR__ . '/src/Exceptions/AppCeleratorHttpException.php';

// Utils
require __DIR__ . '/src/Utils/Response.php';
require __DIR__ . '/src/Utils/Curl.php';

// Controllers
require __DIR__ . '/src/Controllers/Controller.php';
require __DIR__ . '/src/Controllers/CRUDController.php';
require __DIR__ . '/src/Controllers/OAuth2/ClientsController.php';
require __DIR__ . '/src/Controllers/OAuth2/UsersController.php';
require __DIR__ . '/src/Controllers/OAuth2/ScopesController.php';
require __DIR__ . '/src/Controllers/OAuth2/RolesController.php';

// Services
require __DIR__ . '/src/Services/OAuth2Service.php';

// Factories
require __DIR__ . '/src/Factories/ServiceFactory.php';

require __DIR__ . '/src/Client.php';