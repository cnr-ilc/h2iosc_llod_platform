<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ResourceUserRole;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Resource\Pages as CustomPages;
use App\Filament\Admin\Resources\ResourceResource\Pages;
use App\Models\Resource as ResourceModel;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'User resources';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

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
                Pages\ViewResource::class,
                Pages\ViewResourceFiles::class,
            ]);
        }

        return $page->generateNavigationItems($pages);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'view' => Pages\ViewResource::route('/{record}'),
            'files' => Pages\ViewResourceFiles::route('/{record}/files'),
        ];
    }
}
