<?php

namespace Flute\Modules\AmxPrivileges\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Fields\TranslatableInput;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;

class AmxPrivilegesSettingsScreen extends Screen
{
    public ?string $name = 'amxprivileges.admin.title';

    public ?string $description = 'amxprivileges.admin.description';

    public ?string $permission = 'admin.servers';

    protected const PRESETS = [
        'classic' => "abcdefghijklmnopqrstuz = Full Admin\nabcdefg = Admin\ncd = Moderator\nb = VIP\na = Immunity",
        'extended' => "abcdefghijklmnopqrstuz = Owner\nabcdefghijklm = Head Admin\nabcdefg = Admin\ncdef = Moderator\nb = VIP\na = Immunity\nno = Custom 1\npq = Custom 2",
        'simple' => "z = Full Access\nm = RCON Admin\nabcde = Admin\ncd = Moderator\nb = VIP",
    ];

    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('amxprivileges.admin.title'));

        $jsPath = module_path('AmxPrivileges', 'Resources/assets/admin/privilege-editor.js');
        if (is_file($jsPath)) {
            template()->addInlineScript(file_get_contents($jsPath));
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('save'),
        ];
    }

    public function layout(): array
    {
        $privilegeNames = config('amxprivileges.privilege_names', '');

        return [
            LayoutFactory::split([
                $this->mainSettingsBlock(),
                $this->textsBlock(),
            ])->ratio('60/40'),

            $this->privilegeNamesBlock($privilegeNames),
        ];
    }

    protected function mainSettingsBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    Select::make('display_mode')
                        ->options([
                            'cards' => __('amxprivileges.admin.modes.cards'),
                            'table' => __('amxprivileges.admin.modes.table'),
                        ])
                        ->value(config('amxprivileges.display_mode', 'cards'))
                )
                    ->label(__('amxprivileges.admin.labels.display_mode'))
                    ->popover(__('amxprivileges.admin.popovers.display_mode')),

                LayoutFactory::field(
                    Input::make('per_page')
                        ->type('number')
                        ->value(config('amxprivileges.per_page', 20))
                        ->placeholder('20')
                )
                    ->label(__('amxprivileges.admin.labels.per_page'))
                    ->small(__('amxprivileges.admin.smalls.per_page')),
            ]),

            LayoutFactory::field(
                Toggle::make('show_hidden')
                    ->checked(config('amxprivileges.show_hidden', false))
            )
                ->label(__('amxprivileges.admin.labels.show_hidden'))
                ->small(__('amxprivileges.admin.smalls.show_hidden')),

            LayoutFactory::field(
                Toggle::make('show_profile_tab')
                    ->checked(config('amxprivileges.show_profile_tab', true))
            )
                ->label(__('amxprivileges.admin.labels.show_profile_tab'))
                ->small(__('amxprivileges.admin.smalls.show_profile_tab')),

            LayoutFactory::field(
                Toggle::make('hide_flags_from_public')
                    ->checked(config('amxprivileges.hide_flags_from_public', false))
            )
                ->label(__('amxprivileges.admin.labels.hide_flags_from_public'))
                ->small(__('amxprivileges.admin.smalls.hide_flags_from_public')),
        ])
            ->title(__('amxprivileges.admin.blocks.general'))
            ->description(__('amxprivileges.admin.blocks.general_desc'));
    }

    protected function textsBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                TranslatableInput::make('page_title')
                    ->type('text')
                    ->placeholder(__('amxprivileges.admin.placeholders.page_title'))
                    ->value(config('amxprivileges.page_title', ''))
            )
                ->label(__('amxprivileges.admin.labels.page_title'))
                ->small(__('amxprivileges.admin.smalls.page_title')),

            LayoutFactory::field(
                TranslatableInput::make('page_description')
                    ->type('text')
                    ->placeholder(__('amxprivileges.admin.placeholders.page_description'))
                    ->value(config('amxprivileges.page_description', ''))
            )
                ->label(__('amxprivileges.admin.labels.page_description'))
                ->small(__('amxprivileges.admin.smalls.page_description')),
        ])
            ->title(__('amxprivileges.admin.blocks.texts'))
            ->description(__('amxprivileges.admin.blocks.texts_desc'));
    }

    protected function privilegeNamesBlock(string $privilegeNames)
    {
        return LayoutFactory::block([
            LayoutFactory::view('amxprivileges::admin.privilege-editor', [
                'presets' => self::PRESETS,
                'privilegeNames' => $privilegeNames,
            ]),
        ])
            ->title(__('amxprivileges.admin.blocks.privilege_names'))
            ->description(__('amxprivileges.admin.blocks.privilege_names_desc'));
    }

    public function save(): void
    {
        $data = request()->input();

        $config = [
            'display_mode' => in_array($data['display_mode'] ?? '', ['cards', 'table'], true)
                ? $data['display_mode']
                : 'cards',
            'per_page' => max(1, min(100, (int) ($data['per_page'] ?? 20))),
            'show_hidden' => filter_var($data['show_hidden'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'show_profile_tab' => filter_var($data['show_profile_tab'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'hide_flags_from_public' => filter_var($data['hide_flags_from_public'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'page_title' => $data['page_title'] ?? '',
            'page_description' => $data['page_description'] ?? '',
            'privilege_names' => trim((string) ($data['privilege_names'] ?? '')),
        ];

        fs()->updateConfig(
            module_path('AmxPrivileges', 'Resources/config/amxprivileges.php'),
            $config
        );

        $this->flashMessage(__('amxprivileges.admin.messages.saved'), 'success');
    }
}
