<?php

namespace Flute\Modules\AmxPrivileges\Controllers;

use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Modules\AmxPrivileges\Services\AmxPrivilegesService;

class AmxPrivilegesController extends BaseController
{
    #[Route(name: 'amxprivileges.index', uri: '/privileges', methods: ['GET'])]
    public function index(AmxPrivilegesService $service)
    {
        $amxServers = $service->getAmxServers();
        $servers = array_map(fn($e) => $e['server'], $amxServers);
        $validIds = array_map(fn($s) => $s->id, $servers);

        $rawServerId = request()->input('server_id');
        $serverId = $rawServerId !== null ? (int) $rawServerId : null;

        if ($serverId !== null && !in_array($serverId, $validIds, true)) {
            $serverId = null;
        }

        if ($serverId === null && !empty($validIds)) {
            $serverId = $validIds[0];
        }

        $currentAdmins = [];
        $currentServer = null;

        if ($serverId !== null) {
            $data = $service->getActiveAdminsByServer($serverId);

            foreach ($data as $entry) {
                if ($entry['server']->id === $serverId) {
                    $currentAdmins = $entry['admins'];
                    $currentServer = $entry['server'];
                    break;
                }
            }
        }

        if (!$currentServer) {
            foreach ($servers as $s) {
                if ($s->id === $serverId) {
                    $currentServer = $s;
                    break;
                }
            }
        }

        $displayMode = config('amxprivileges.display_mode', 'cards');
        $perPage = config('amxprivileges.per_page', 20);
        $canSeeFlags = !config('amxprivileges.hide_flags_from_public', false)
            || (user()->isLoggedIn() && user()->can('admin.boss'));

        $page = max(1, (int) request()->input('page', 1));
        $totalAdmins = count($currentAdmins);
        $totalPages = max(1, (int) ceil($totalAdmins / $perPage));
        $page = min($page, $totalPages);
        $pagedAdmins = array_slice($currentAdmins, ($page - 1) * $perPage, $perPage);

        $pageTitle = config('amxprivileges.page_title', '');
        $pageTitle = !empty($pageTitle) ? transValue($pageTitle) : __('amxprivileges.title');

        $pageDescription = config('amxprivileges.page_description', '');
        $pageDescription = !empty($pageDescription) ? transValue($pageDescription) : __('amxprivileges.description');

        breadcrumb()->add($pageTitle, route('amxprivileges.index'));

        return view('amxprivileges::index', [
            'servers' => $servers,
            'currentServerId' => $serverId,
            'currentServer' => $currentServer,
            'admins' => $pagedAdmins,
            'displayMode' => $displayMode,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalAdmins' => $totalAdmins,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'canSeeFlags' => $canSeeFlags,
        ]);
    }

    #[Route(name: 'amxprivileges.detail', uri: '/privileges/detail/{serverId}/{adminId}', methods: ['GET'], middleware: ['htmx'])]
    public function detail(int $serverId, int $adminId, AmxPrivilegesService $service)
    {
        if ($serverId <= 0 || $adminId <= 0) {
            return $this->error(__('def.invalid_request'), 400);
        }

        $admin = $service->getAdminById($serverId, $adminId);

        if (!$admin) {
            return $this->error(__('amxprivileges.admin_not_found'), 404);
        }

        $canSeeFlags = !config('amxprivileges.hide_flags_from_public', false)
            || (user()->isLoggedIn() && user()->can('admin.boss'));

        return $this->htmxRender('amxprivileges::partials.detail', [
            'admin' => $admin,
            'canSeeFlags' => $canSeeFlags,
        ])->setTriggers(['open-modal' => 'amxpriv-detail-modal']);
    }

    #[Route(name: 'amxprivileges.change_password', uri: '/privileges/change-password', methods: ['POST'], middleware: ['auth', 'csrf'])]
    public function changePassword(AmxPrivilegesService $service)
    {
        $user = user()->getCurrentUser();
        if (!$user) {
            return $this->error(__('auth.login_required'), 401);
        }

        $newPassword = trim((string) request()->input('new_password'));

        if (strlen($newPassword) < 3 || strlen($newPassword) > 32) {
            return $this->error(__('amxprivileges.profile.password_length'), 422);
        }

        if (preg_match('/[\x00-\x1f\x7f"\'\\\\;]/', $newPassword)) {
            return $this->error(__('amxprivileges.profile.password_invalid_chars'), 422);
        }

        $steam = $user->getSocialNetwork('Steam') ?? $user->getSocialNetwork('HttpsSteam');
        if (!$steam?->value) {
            return $this->error(__('amxprivileges.profile.cannot_identify'), 400);
        }

        try {
            $steamId = steam()->steamid($steam->value)->RenderSteam2();
        } catch (\Throwable $e) {
            return $this->error(__('amxprivileges.profile.cannot_identify'), 400);
        }

        $updated = $service->changePassword($steamId, $newPassword);

        if (!$updated) {
            return $this->error(__('amxprivileges.profile.admin_not_found'), 404);
        }

        $this->toast(__('amxprivileges.profile.password_changed'), 'success');

        return $this->success(__('amxprivileges.profile.password_changed'));
    }
}
