<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\DSpaceServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\AuthPanelProvider::class,
    App\Providers\Filament\UserPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\SemanticTurkeyServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
