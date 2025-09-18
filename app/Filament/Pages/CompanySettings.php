<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Models\Company;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CompanySettings extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions, WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected string $view = 'filament.pages.company-settings';
    protected static ?string $title = 'Configuración de Empresa';
    protected static ?string $navigationLabel = 'Configuración';
    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public Company $company;

    public function mount(): void
    {
        $this->company = auth()->user()->company;
        $this->form->fill($this->company->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Información Básica')
                    ->description('Información principal de tu empresa')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Identificador URL')
                            ->helperText('Se usará en la URL del perfil: /empresa/tu-slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel(),

                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->prefix('https://')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Perfil Social')
                    ->description('Personaliza como aparece tu empresa en la red social')
                    ->schema([
                        Textarea::make('bio')
                            ->label('Descripción de la Empresa')
                            ->helperText('Describe qué hace tu empresa (máximo 500 caracteres)')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('avatar')
                            ->label('Logo/Avatar')
                            ->image()
                            ->directory('companies/avatars')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200'),

                        FileUpload::make('banner')
                            ->label('Banner/Portada')
                            ->image()
                            ->directory('companies/banners')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('450'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Redes Sociales')
                    ->description('Enlaces a tus redes sociales')
                    ->schema([
                        TextInput::make('facebook')
                            ->label('Facebook')
                            ->url()
                            ->prefix('https://facebook.com/'),

                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->url()
                            ->prefix('https://instagram.com/'),

                        TextInput::make('twitter')
                            ->label('Twitter/X')
                            ->url()
                            ->prefix('https://twitter.com/'),

                        TextInput::make('linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->prefix('https://linkedin.com/company/'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Configuración de Privacidad')
                    ->description('Controla la visibilidad y configuraciones de tu perfil')
                    ->schema([
                        Toggle::make('is_public')
                            ->label('Perfil Público')
                            ->helperText('Si está desactivado, solo usuarios autenticados podrán ver tu perfil')
                            ->default(true),

                        Toggle::make('allow_followers')
                            ->label('Permitir Seguidores')
                            ->helperText('Permite que otras empresas te sigan')
                            ->default(true),

                        Toggle::make('show_contact_info')
                            ->label('Mostrar Información de Contacto')
                            ->helperText('Muestra email y teléfono en el perfil público')
                            ->default(true),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->statePath('data')
            ->model($this->company);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Configuración')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $this->company->update($data);

            Notification::make()
                ->title('Configuración guardada')
                ->success()
                ->send();

        } catch (\Exception $exception) {
            Notification::make()
                ->title('Error al guardar')
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }
}
