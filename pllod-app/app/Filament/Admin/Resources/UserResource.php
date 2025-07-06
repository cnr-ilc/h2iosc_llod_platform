<?php

namespace App\Filament\Admin\Resources;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label(__('Last Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label(__('Email'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->label(__('Password'))
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->options(fn () => Role::where('name', '!=', UserRole::SUPER_ADMIN->value)->pluck('name', 'id'))
                    ->preload()
                    ->hidden(fn (?User $record, string $operation): bool => $operation === 'edit' && $record?->hasRole(UserRole::SUPER_ADMIN->value))
                    ->label(__('Roles'))
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('last_name')
                    ->sortable()
                    ->searchable()
                    ->label(__('Last Name')),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->label(__('Email'))
                    ->copyable()
                    ->copyMessage(__('Email copied'))
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Roles'))
                    ->badge()
                    ->color(color: fn (string $state): string => UserRole::from($state)->getColor()),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->label(__('Registered at'))
                    ->dateTime('d-m-Y H:i:s'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->options(fn () => Role::pluck('name', 'name'))
                    ->label(__('Roles')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->hasRole(UserRole::SUPER_ADMIN->value) && $record->id === Auth::user()->id),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
