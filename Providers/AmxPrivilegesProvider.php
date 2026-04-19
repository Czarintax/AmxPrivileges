<?php

namespace Flute\Modules\AmxPrivileges\Providers;

use Flute\Core\Modules\Profile\Services\ProfileTabService;
use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\AmxPrivileges\Admin\Package\AmxPrivilegesPackage;
use Flute\Modules\AmxPrivileges\Services\AmxPrivilegesService;
use Flute\Modules\AmxPrivileges\Tabs\AmxPrivilegesProfileTab;

class AmxPrivilegesProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\Container $container): void
    {
        $this->loadConfigs();

        $this->bootstrapModule();

        $this->loadViews('Resources/views', 'amxprivileges');
        $this->loadScss('Resources/assets/scss/main.scss');

        $this->loadPackage(new AmxPrivilegesPackage());

        if (config('amxprivileges.show_profile_tab', true)
            && $container->has(ProfileTabService::class)
        ) {
            $container->get(ProfileTabService::class)->registerTab(new AmxPrivilegesProfileTab());
        }
    }

    public function register(\DI\Container $container): void
    {
        $container->set(AmxPrivilegesService::class, \DI\create(AmxPrivilegesService::class));
    }
}
