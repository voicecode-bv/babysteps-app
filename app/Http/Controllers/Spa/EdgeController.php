<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Native\Mobile\Edge\Edge;

class EdgeController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function setActiveTab(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $this->normalizePath($validated['path']);

        if ($this->isOnboardingPath($path)) {
            Edge::clear();

            return response()->json(['cleared' => true]);
        }

        $activeTab = $this->resolveActiveTab($path);

        $this->setupBottomNav($activeTab);

        return response()->json(['active' => $activeTab]);
    }

    protected function normalizePath(string $path): string
    {
        $parsed = parse_url($path, PHP_URL_PATH) ?: $path;

        return '/'.ltrim($parsed, '/');
    }

    protected function isOnboardingPath(string $path): bool
    {
        return str_starts_with($path, '/onboarding/');
    }

    protected function resolveActiveTab(string $path): string
    {
        return match (true) {
            $path === '/' => 'home',
            str_starts_with($path, '/circles') => 'circles',
            $path === '/posts/create' => 'add',
            str_starts_with($path, '/notifications') => 'notifications',
            str_starts_with($path, '/settings') => 'settings',
            default => 'home',
        };
    }

    protected function getUnreadNotificationCount(): int
    {
        $count = Cache::get('unread_notification_count');

        if ($count !== null) {
            return $count;
        }

        try {
            $response = $this->apiClient->get('/notifications/unread-count');
            $count = $response->successful() ? (int) $response->json('count', 0) : 0;
        } catch (ConnectionException) {
            $count = 0;
        }

        Cache::put('unread_notification_count', $count, 60);

        return $count;
    }

    protected function setupBottomNav(string $activeTab): void
    {
        $unreadCount = $this->getUnreadNotificationCount();

        $contextIndex = Edge::startContext();

        Edge::add('bottom_nav_item', [
            'id' => 'home',
            'icon' => 'home',
            'label' => __('Home'),
            'url' => url('/'),
            'active' => $activeTab === 'home',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'circles',
            'icon' => 'person.2',
            'label' => __('Circles'),
            'url' => url('/circles'),
            'active' => $activeTab === 'circles',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'add',
            'icon' => 'plus.circle',
            'label' => __('New'),
            'url' => url('/posts/create'),
            'active' => $activeTab === 'add',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'notifications',
            'icon' => 'bell',
            'label' => __('Notifications'),
            'url' => url('/notifications'),
            'active' => $activeTab === 'notifications',
            'badge' => $unreadCount > 0 ? (string) $unreadCount : null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'settings',
            'icon' => 'gearshape',
            'label' => __('Settings'),
            'url' => url('/settings'),
            'active' => $activeTab === 'settings',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::endContext($contextIndex, 'bottom_nav', [
            'id' => 'bottom_nav',
            'dark' => null,
            'label_visibility' => 'labeled',
            'active_color' => null,
        ]);

        Edge::set();
    }
}
