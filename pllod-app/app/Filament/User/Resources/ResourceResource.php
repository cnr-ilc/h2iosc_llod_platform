<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\ResourceResource\Pages;
use App\Filament\User\Resources\ResourceResource\Pages\ViewResource;
use App\Filament\User\Resources\ResourceResource\Pages\ViewResourceFiles;
use App\Models\Resource as ResourceModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name;
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
            // 'index' => Pages\ListResources::route('/'),
            // 'create' => Pages\CreateResource::route('/create'),
            // 'edit'   => Pages\EditResource::route('/{record}/edit'),
            'view' => ViewResource::route('/{record}'),
            'files' => ViewResourceFiles::route('/{record}/files'),
        ];
    }
}
