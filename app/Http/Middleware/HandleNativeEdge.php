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
        if (! auth()->check()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === 'posts.create') {
            Edge::clear();

            return $next($request);
        }

        $this->setupBottomNav($request);

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
            'active' => false,
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
            'badge' => null,
            'badge_color' => null,
            'news' => false,
        ]);

        Edge::add('bottom_nav_item', [
            'id' => 'profile',
            'icon' => 'person',
            'label' => __('Profile'),
            'url' => route('profiles.show', ['username' => auth()->user()->username]),
            'active' => str_starts_with($routeName ?? '', 'profiles'),
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
