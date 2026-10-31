<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => [
        'include' => 'api',
        'exclude' => ['api/swagger', 'api/swagger.json'],
    ],
    'api_domain' => null,
    'ui' => ['title' => 'Opeflow Auth API'],
    'info' => [
        'version' => '1.0.0',
        'description' => 'Central de autenticação multi-sistema. A especificação acompanha automaticamente as rotas e validações da API.',
    ],
    'middleware' => [RestrictedDocsAccess::class],
    'security_strategy' => [
        Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['jwt', App\Http\Middleware\VerificarTokenKeycloak::class],
            'scheme' => Dedoc\Scramble\Support\Generator\SecurityScheme::http('bearer'),
        ],
    ],
];
