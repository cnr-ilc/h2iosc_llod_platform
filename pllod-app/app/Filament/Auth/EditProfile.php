<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    /**
     * Filament 4+: il form del profilo si definisce con form(Schema) (getForms()/makeForm() non esistono più).
     * Come in v3 non includiamo getCurrentPasswordFormComponent(): gli utenti Keycloak non hanno una password locale nota.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getLastNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getLastNameFormComponent(): TextInput
    {
        return TextInput::make('last_name')
            ->label('Last Name');
    }
}
