<?php

namespace Flute\Modules\AmxPrivileges\Tabs;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;
use Flute\Modules\AmxPrivileges\Services\AmxPrivilegesService;

class AmxPrivilegesProfileTab extends ProfileTab
{
    public function getId(): string
    {
        return 'amx-privileges';
    }

    public function getPath(): string
    {
        return 'privileges';
    }

    public function getTitle(): string
    {
        return __('amxprivileges.profile.title');
    }

    public function getDescription(): ?string
    {
        return __('amxprivileges.profile.description');
    }

    public function getIcon(): ?string
    {
        return 'ph.regular.shield-star';
    }

    public function getOrder(): int
    {
        return 140;
    }

    public function canView(User $user): bool
    {
        if (!user()->isLoggedIn()) {
            return false;
        }

        return user()->id === $user->id || user()->can('admin.users');
    }

    public function getContent(User $user)
    {
        $service = app(AmxPrivilegesService::class);
        $steamId = $this->getUserSteamId($user);

        $privileges = [];

        if ($steamId) {
            $privileges = $service->getPrivilegesForSteamId($steamId);
        }

        $active = [];
        $expired = [];

        foreach ($privileges as $priv) {
            if ($priv['isActive']) {
                $active[] = $priv;
            } else {
                $expired[] = $priv;
            }
        }

        $isOwner = user()->id === $user->id;
        $showPasswordModal = $isOwner && !empty(array_filter($active, static fn($p) => $p['usesPassword'] || $p['hasPassword']));
        $canSeeFlags = !config('amxprivileges.hide_flags_from_public', false)
            || user()->can('admin.boss');

        return view('amxprivileges::profile.privileges', [
            'user' => $user,
            'activePrivileges' => $active,
            'expiredPrivileges' => $expired,
            'isOwner' => $isOwner,
            'showPasswordModal' => $showPasswordModal,
            'canSeeFlags' => $canSeeFlags,
            'changePasswordUrl' => route('amxprivileges.change_password'),
        ]);
    }

    protected function getUserSteamId(User $user): ?string
    {
        $steam = $user->getSocialNetwork('Steam') ?? $user->getSocialNetwork('HttpsSteam');
        if (!$steam?->value) {
            return null;
        }

        try {
            return steam()->steamid($steam->value)->RenderSteam2();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
