<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Lets admins manage site-wide branding & content — logo, favicon, title,
 * description, "About us" and "Contact us" details — without touching
 * code. Values are stored as key/value SystemSetting rows and read by the
 * public frontend (see layouts.app, pages.about, pages.contact).
 */
class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static ?string $title = 'Platform Settings';

    protected static string $view = 'filament.pages.platform-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => SystemSetting::get('site_name', config('app.name')),
            'site_tagline' => SystemSetting::get('site_tagline', 'Live betting, digital pool & snooker tournaments.'),
            'site_description' => SystemSetting::get('site_description', 'Play Snooker bridges physical and digital snooker & pool tournaments with live betting, social multiplayer and an in-game marketplace.'),
            'logo_path' => SystemSetting::get('logo_path'),
            'favicon_path' => SystemSetting::get('favicon_path'),
            'about_us' => SystemSetting::get('about_us', '<p>Play Snooker is the home of competitive pool and snooker online.</p>'),
            'contact_email' => SystemSetting::get('contact_email', 'support@playsnooker.bet'),
            'contact_phone' => SystemSetting::get('contact_phone'),
            'contact_address' => SystemSetting::get('contact_address'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Branding')
                    ->description('Shown in the browser tab, site header and social share previews.')
                    ->schema([
                        TextInput::make('site_name')->label('Platform title')->required()->maxLength(60),
                        TextInput::make('site_tagline')->label('Tagline')->maxLength(120),
                        Textarea::make('site_description')->label('Meta description')->rows(2)->maxLength(300),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->helperText('Displayed in the site navigation. Falls back to the default emoji mark if left empty.'),
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->helperText('Falls back to the default icon if left empty.'),
                    ])
                    ->columns(2),

                Section::make('About us')
                    ->schema([
                        RichEditor::make('about_us')
                            ->label('About us page content')
                            ->columnSpanFull(),
                    ]),

                Section::make('Contact us')
                    ->schema([
                        TextInput::make('contact_email')->label('Support email')->email(),
                        TextInput::make('contact_phone')->label('Phone number'),
                        Textarea::make('contact_address')->label('Postal address')->rows(2),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SystemSetting::set($key, is_array($value) ? json_encode($value) : (string) $value, 'string', 'branding');
        }

        Notification::make()
            ->title('Platform settings saved')
            ->success()
            ->send();
    }
}
