<?php

use Flute\Core\Router\Router;
use Flute\Modules\AmxPrivileges\Admin\Package\Screens\AmxPrivilegesSettingsScreen;

Router::screen('/admin/amx-privileges', AmxPrivilegesSettingsScreen::class);
