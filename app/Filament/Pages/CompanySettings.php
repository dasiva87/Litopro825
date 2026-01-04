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
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

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

    public function getHeading(): string
    {
        return 'Configuración de Empresa';
    }

    public function getSubheading(): ?string
    {
        return 'Personaliza la información y apariencia de tu empresa en la red Grafired';
    }

    public function mount(): void
    {
        $this->company = auth()->user()->company;

        // Cargar datos del formulario
        $data = $this->company->toArray();

        // NO cargar archivos existentes en el formulario
        // FileUpload mostrará un campo vacío, pero preservaremos los archivos en save()
        unset($data['avatar']);
        unset($data['banner']);

        $this->form->fill($data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Tabs::make('Configuración')
                    ->tabs([
                        Tabs\Tab::make('Información')
                            ->icon('heroicon-m-building-office')
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
                            ->columns(2),

                        Tabs\Tab::make('Perfil Social')
                            ->icon('heroicon-m-user-circle')
                            ->schema([
                                Textarea::make('bio')
                                    ->label('Descripción de la Empresa')
                                    ->helperText('Describe qué hace tu empresa (máximo 500 caracteres)')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Placeholder::make('current_avatar')
                                    ->label('Logo/Avatar Actual')
                                    ->content(function () {
                                        if ($this->company->avatar && Storage::disk('public')->exists($this->company->avatar)) {
                                            $url = Storage::disk('public')->url($this->company->avatar);
                                            return new HtmlString('<img src="' . $url . '" style="max-width: 200px; border-radius: 8px;" />');
                                        }
                                        return 'Sin logo/avatar';
                                    })
                                    ->columnSpanFull(),

                                FileUpload::make('avatar')
                                    ->label('Nuevo Logo/Avatar (opcional)')
                                    ->helperText('Selecciona una imagen solo si deseas cambiar el logo actual')
                                    ->image()
                                    ->disk('public')
                                    ->directory('companies/avatars')
                                    ->visibility('public')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('200')
                                    ->imageResizeTargetHeight('200')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                Placeholder::make('current_banner')
                                    ->label('Banner/Portada Actual')
                                    ->content(function () {
                                        if ($this->company->banner && Storage::disk('public')->exists($this->company->banner)) {
                                            $url = Storage::disk('public')->url($this->company->banner);
                                            return new HtmlString('<img src="' . $url . '" style="max-width: 100%; border-radius: 8px;" />');
                                        }
                                        return 'Sin banner/portada';
                                    })
                                    ->columnSpanFull(),

                                FileUpload::make('banner')
                                    ->label('Nuevo Banner/Portada (opcional)')
                                    ->helperText('Selecciona una imagen solo si deseas cambiar el banner actual')
                                    ->image()
                                    ->disk('public')
                                    ->directory('companies/banners')
                                    ->visibility('public')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('16:9')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('450')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->imageEditor()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),

                        Tabs\Tab::make('Redes')
                            ->icon('heroicon-m-share')
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
                            ->columns(2),

                        Tabs\Tab::make('Privacidad')
                            ->icon('heroicon-m-lock-closed')
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
                            ->columns(1),
                    ])
                    ->columnSpanFull(),
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

            // Filament FileUpload maneja automáticamente la carga de archivos
            // Solo necesitamos limpiar valores null para mantener archivos existentes
            if (array_key_exists('avatar', $data) && is_null($data['avatar'])) {
                unset($data['avatar']);
            }

            if (array_key_exists('banner', $data) && is_null($data['banner'])) {
                unset($data['banner']);
            }

            $this->company->update($data);

            // Refrescar el modelo desde la base de datos
            $this->company->refresh();

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
