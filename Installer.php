<?php

namespace Flute\Modules\AmxPrivileges;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\Support\AbstractModuleInstaller;

class Installer extends AbstractModuleInstaller
{
    public function install(ModuleInformation &$module): bool
    {
        $existingNavbar = NavbarItem::findOne(['url' => '/privileges']);
        if (!$existingNavbar) {
            $navbarItem = new NavbarItem();
            $navbarItem->title = __('amxprivileges.title');
            $navbarItem->url = '/privileges';
            $navbarItem->new_tab = false;
            $navbarItem->save();
        }

        return true;
    }

    public function uninstall(ModuleInformation &$module): bool
    {
        $navbarItem = NavbarItem::findOne(['url' => '/privileges']);
        if ($navbarItem) {
            $navbarItem->delete();
        }

        return true;
    }
}
