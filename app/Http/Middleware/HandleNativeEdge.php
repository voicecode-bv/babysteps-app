<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Native\Mobile\Edge\Edge;
use Symfony\Component\HttpFoundation\Response;

class HandleNativeEdge
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.platform') === 'web') {
            return $next($request);
        }

        if ($request->user()) {
            $this->setupBottomNav($request);
        } else {
            Edge::clear();
        }

        return $next($request);
    }

    protected function setupBottomNav(Request $request): void
    {
        $routeName = $request->route()?->getName();

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
            'url' => route('feed'),
            'active' => $routeName === 'circles',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'add',
            'icon' => 'plus.circle',
            'label' => __('New'),
            'url' => route('feed'),
            'active' => false,
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'notifications',
            'icon' => 'bell',
            'label' => __('Notifications'),
            'url' => route('feed'),
            'active' => $routeName === 'notifications',
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'profile',
            'icon' => 'person',
            'label' => __('Profile'),
            'url' => route('feed'),
            'active' => $routeName === 'profile',
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
