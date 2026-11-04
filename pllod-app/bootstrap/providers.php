<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\DSpaceServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AuthPanelProvider;
use App\Providers\Filament\UserPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\SemanticTurkeyServiceProvider;
use OwenIt\Auditing\AuditingServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    DSpaceServiceProvider::class,
    AdminPanelProvider::class,
    AuthPanelProvider::class,
    UserPanelProvider::class,
    HorizonServiceProvider::class,
    SemanticTurkeyServiceProvider::class,
    AuditingServiceProvider::class,
    PermissionServiceProvider::class,
];
