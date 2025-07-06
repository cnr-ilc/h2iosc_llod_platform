<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SettingsResource\Pages;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SettingsResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function canCreate(): bool
    {
        return false;
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
                Tables\Columns\TextColumn::make('label')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('Value'))
                    ->formatStateUsing(fn (string $state, Setting $record) => $record->type === 'file' ? basename($state) : $state),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->label(__('Updated at'))
                    ->sortable(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hidden(fn (Setting $record): bool => $record->type !== 'file' || ($record->type === 'file' && empty($record->value)))
                    ->url(fn (Setting $record): string => Storage::url($record->value))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->form(function (Setting $record): array {
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
            'index' => Pages\ManageSettings::route('/'),
        ];
    }
}
