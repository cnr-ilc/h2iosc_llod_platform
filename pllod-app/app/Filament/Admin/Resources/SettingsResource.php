<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SettingsResource\Pages\ManageSettings;
use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Storage;

class SettingsResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getCreateAuthorizationResponse(): Response
    {
        return Response::deny();
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
                TextColumn::make('label')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('value')
                    ->label(__('Value'))
                    ->formatStateUsing(fn (string $state, Setting $record) => $record->type === 'file' ? basename($state) : $state),
                TextColumn::make('updated_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->label(__('Updated at'))
                    ->sortable(),
            ])
            ->paginated(false)
            ->recordActions([
                ViewAction::make()
                    ->hidden(fn (Setting $record): bool => $record->type !== 'file' || ($record->type === 'file' && empty($record->value)))
                    ->url(fn (Setting $record): string => Storage::url($record->value))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->schema(function (Setting $record): array {
                        return match ($record->type) {
                            'file' => [
                                FileUpload::make('value')
                                    ->label($record->label)
                                    ->disk('public')
                                    ->directory(fn (Setting $record): string => "files/{$record->key}")
                                    ->preserveFilenames(),
                            ],
                            'select' => [
                                Select::make('value')
                                    ->label($record->label)
                                    ->options($record->attributes['options']),
                            ],
                            'number' => [
                                TextInput::make('value')
                                    ->label($record->label)
                                    ->type('number'),
                            ],
                            default => [
                                TextInput::make('value')
                                    ->label($record->label),
                            ]
                        };
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettings::route('/'),
        ];
    }
}
