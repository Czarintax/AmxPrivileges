<?php

namespace Flute\Modules\AmxPrivileges\Admin\Package;

use Flute\Admin\Support\AbstractAdminPackage;

class AmxPrivilegesPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');
        $this->registerScss('../../Resources/assets/scss/admin/privilege-editor.scss');
    }

    public function getPermissions(): array
    {
        return ['admin', 'admin.servers'];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'icon' => 'ph.bold.shield-star-bold',
                'title' => __('amxprivileges.admin.title'),
                'url' => url('/admin/amx-privileges'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 125;
    }
}
