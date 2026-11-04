<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ResourceUserRole;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Resource\Pages as CustomPages;
use App\Filament\Admin\Resources\ResourceResource\Pages;
use App\Filament\Admin\Resources\ResourceResource\Pages\ViewResource;
use App\Filament\Admin\Resources\ResourceResource\Pages\ViewResourceFiles;
use App\Models\Resource as ResourceModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'User resources';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user()->hasRole([UserRole::ADMIN->value]);
    }

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $owner = $record->users()->where('role', ResourceUserRole::OWNER)->first();

        return [
            'Owned by' => "{$owner->name} {$owner->last_name}",
            'Type' => $record->type,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $pages = [];

        if ($page->getRecord()->files->isNotEmpty()) {
            $pages = array_merge($pages, [
                ViewResource::class,
                ViewResourceFiles::class,
            ]);
        }

        return $page->generateNavigationItems($pages);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        // https://www.answeroverflow.com/m/1152311967575457862
        return [
            // 'create' => Pages\CreateResource::route('/create'),
            // 'edit' => Pages\EditResource::route('/{record}/edit'),
            // 'index' => Pages\ListResources::route('/'),
            'index' => CustomPages\ListResource::route('/'),
            'view' => ViewResource::route('/{record}'),
            'files' => ViewResourceFiles::route('/{record}/files'),
        ];
    }
}
