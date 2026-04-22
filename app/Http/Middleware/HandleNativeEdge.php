<?php

namespace App\Http\Middleware;

use App\Services\ApiClient;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Native\Mobile\Edge\Edge;
use Symfony\Component\HttpFoundation\Response;

class HandleNativeEdge
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($this->shouldClearEdge($routeName)) {
            Edge::clear();

            return $next($request);
        }

        $response = $next($request);

        $this->setupBottomNav($request);

        return $response;
    }

    protected function shouldClearEdge(?string $routeName): bool
    {
        return in_array($routeName, [
            'onboarding.intro',
            'onboarding.first-circle',
            'onboarding.invite-members',
            'onboarding.notifications',
        ]);
    }

    protected function getUnreadNotificationCount(): int
    {
        $count = Cache::get('unread_notification_count');

        if ($count !== null) {
            return $count;
        }

        try {
            $response = app(ApiClient::class)->get('/notifications/unread-count');
            $count = $response->successful() ? (int) $response->json('count', 0) : 0;
        } catch (ConnectionException) {
            $count = 0;
        }

        Cache::put('unread_notification_count', $count, 60);

        return $count;
    }

    protected function setupBottomNav(Request $request): void
    {
        $routeName = $request->route()?->getName();
        $unreadCount = $this->getUnreadNotificationCount();

        $contextIndex = Edge::startContext();

        Edge::add('bottom_nav_item', [
            'id' => 'home',
            'icon' => 'home',
            'label' => __('Home'),
            'url' => route('feed'),
            'active' => $routeName === 'feed',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'circles',
            'icon' => 'person.2',
            'label' => __('Circles'),
            'url' => route('circles.index'),
            'active' => str_starts_with($routeName ?? '', 'circles'),
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'add',
            'icon' => 'plus.circle',
            'label' => __('New'),
            'url' => route('posts.create'),
            'active' => $routeName === 'posts.create',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'notifications',
            'icon' => 'bell',
            'label' => __('Notifications'),
            'url' => route('notifications'),
            'active' => $routeName === 'notifications',
            'badge' => $unreadCount > 0 ? (string) $unreadCount : null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'settings',
            'icon' => 'gearshape',
            'label' => __('Settings'),
            'url' => route('settings'),
            'active' => $routeName === 'settings',
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
