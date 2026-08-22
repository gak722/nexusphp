<?php

/*
|--------------------------------------------------------------------------
| Application Services & Dependency Injection Declarations
|--------------------------------------------------------------------------
|
| Declare your application dependencies in array configuration format,
| exactly like all other framework configuration files (`config/*.php`).
|
| Supported Lifetimes (.NET IServiceCollection style):
|  - 'singleton' : Single shared instance across application lifecycle
|  - 'transient' : New instance created per resolve request
|  - 'scoped'    : Bound instance for current request scope
|
*/

return [

    /*
     * Array-based service bindings mapping Abstract => Concrete
     *
     * Example:
     * 'singletons' => [
     *     App\Services\PaymentGatewayInterface::class => App\Services\StripePaymentGateway::class,
     * ],
     * 'transients' => [
     *     App\Services\InvoiceGenerator::class => App\Services\InvoiceGenerator::class,
     * ],
     * 'scoped' => [
     *     App\Repositories\UserRepositoryInterface::class => App\Repositories\UserRepository::class,
     * ],
     */
    'singletons' => [
        // App\Contracts\MailerInterface::class => App\Services\SmtpMailer::class,
    ],

    'transients' => [
        // App\Services\PdfGenerator::class => App\Services\PdfGenerator::class,
    ],

    'scoped' => [
        // App\Services\UserSession::class => App\Services\UserSession::class,
    ],

    /*
     * Closure-based / Callback Registrations (optional)
     * You can pass a closure to perform custom container registration.
     */
    'register' => function (\Nexus\Foundation\Application $services): void {
        // $services->addSingleton(App\Contracts\PaymentInterface::class, fn() => new App\Services\Stripe());
    },

];
