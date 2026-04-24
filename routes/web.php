<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CircleActionController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\CreatePostController;
use App\Http\Controllers\DefaultCircleController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MediaProxyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\Onboarding\CompleteController as OnboardingCompleteController;
use App\Http\Controllers\Onboarding\FirstCircleController as OnboardingFirstCircleController;
use App\Http\Controllers\Onboarding\IntroController as OnboardingIntroController;
use App\Http\Controllers\Onboarding\InviteMembersController as OnboardingInviteMembersController;
use App\Http\Controllers\Onboarding\NotificationController as OnboardingNotificationController;
use App\Http\Controllers\PostActionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\HandleNativeEdge;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::get('/auth/verify', [AuthController::class, 'verify'])->name('auth.verify');

Route::get('/oauth/callback', [SocialAuthController::class, 'callback'])->name('oauth.callback');

Route::put('/locale', [LoginController::class, 'updateLocale'])->name('locale.update');
Route::get('/media-proxy', MediaProxyController::class)->name('media-proxy');

Route::middleware(['auth.token', HandleNativeEdge::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/device-token', [DeviceTokenController::class, 'store'])->name('device-token.store');
    Route::get('/onboarding/intro', OnboardingIntroController::class)->name('onboarding.intro');
    Route::get('/onboarding/first-circle', [OnboardingFirstCircleController::class, 'show'])->name('onboarding.first-circle');
    Route::post('/onboarding/first-circle', [OnboardingFirstCircleController::class, 'store'])->name('onboarding.first-circle.store');
    Route::get('/onboarding/circles/{circle}/invite', [OnboardingInviteMembersController::class, 'show'])->name('onboarding.invite-members')->whereNumber('circle');
    Route::post('/onboarding/circles/{circle}/invite', [OnboardingInviteMembersController::class, 'store'])->name('onboarding.invite-members.store')->whereNumber('circle');
    Route::get('/onboarding/notifications', OnboardingNotificationController::class)->name('onboarding.notifications');
    Route::post('/onboarding/complete', OnboardingCompleteController::class)->name('onboarding.complete');
});

Route::middleware(['auth.token', HandleNativeEdge::class, EnsureOnboarded::class])->group(function () {
    Route::get('/', FeedController::class)->name('feed');
    Route::get('/circles/{circle}/feed', [FeedController::class, 'circle'])->name('circles.feed')->whereNumber('circle');
    Route::get('/posts/create', CreatePostController::class)->name('posts.create');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show')->whereNumber('post');

    Route::get('/native-media', [PostActionController::class, 'serveMedia'])->name('native-media');
    Route::post('/posts/cropped-media', [PostActionController::class, 'storeCroppedMedia'])->name('posts.cropped-media');
    Route::post('/posts', [PostActionController::class, 'store'])->name('posts.store');
    Route::put('/posts/{post}', [PostActionController::class, 'update'])->name('posts.update')->whereNumber('post');
    Route::delete('/posts/{post}', [PostActionController::class, 'destroy'])->name('posts.destroy')->whereNumber('post');
    Route::get('/posts/{post}/likes', [PostActionController::class, 'indexLikes'])->name('posts.likes.index')->whereNumber('post');
    Route::post('/posts/{post}/like', [PostActionController::class, 'like'])->name('posts.like')->whereNumber('post');
    Route::delete('/posts/{post}/like', [PostActionController::class, 'unlike'])->name('posts.unlike')->whereNumber('post');
    Route::get('/posts/{post}/comments', [PostActionController::class, 'indexComments'])->name('posts.comments.index')->whereNumber('post');
    Route::post('/posts/{post}/comments', [PostActionController::class, 'comment'])->name('posts.comments.store')->whereNumber('post');
    Route::delete('/comments/{comment}', [PostActionController::class, 'destroyComment'])->name('comments.destroy')->whereNumber('comment');
    Route::post('/comments/{comment}/like', [PostActionController::class, 'likeComment'])->name('comments.like')->whereNumber('comment');
    Route::delete('/comments/{comment}/like', [PostActionController::class, 'unlikeComment'])->name('comments.unlike')->whereNumber('comment');

    Route::get('/map', [MapController::class, 'show'])->name('map');
    Route::get('/photos/map', [MapController::class, 'photos'])->name('photos.map');
    Route::get('/profiles/{username}/map', [MapController::class, 'profile'])->name('profiles.map');
    Route::get('/profiles/{username}/photos/map', [MapController::class, 'profilePhotos'])->name('profiles.photos.map');
    Route::get('/circles/{circle}/map', [MapController::class, 'circle'])->name('circles.map')->whereNumber('circle');
    Route::get('/circles/{circle}/photos/map', [MapController::class, 'circlePhotos'])->name('circles.photos.map')->whereNumber('circle');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::get('/settings/account', [AccountController::class, 'show'])->name('settings.account');
    Route::post('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::get('/profiles/{username}', [ProfileController::class, 'show'])->name('profiles.show');
    Route::put('/profile/bio', [ProfileController::class, 'updateBio'])->name('profile.update-bio');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');
    Route::put('/profile/locale', [ProfileController::class, 'updateLocale'])->name('profile.update-locale');

    Route::get('/profile/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('profile.notification-preferences');
    Route::put('/profile/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('profile.notification-preferences.update');

    Route::get('/profile/default-circles', [DefaultCircleController::class, 'index'])->name('profile.default-circles');
    Route::put('/profile/default-circles', [DefaultCircleController::class, 'update'])->name('profile.default-circles.update');

    Route::get('/circles', [CircleController::class, 'index'])->name('circles.index');
    Route::get('/circles/{circle}', [CircleController::class, 'show'])->name('circles.show')->whereNumber('circle');
    Route::post('/circles', [CircleActionController::class, 'store'])->name('circles.store');
    Route::post('/circles/{circle}/photo', [CircleActionController::class, 'updatePhoto'])->name('circles.update-photo')->whereNumber('circle');
    Route::delete('/circles/{circle}/photo', [CircleActionController::class, 'deletePhoto'])->name('circles.delete-photo')->whereNumber('circle');
    Route::put('/circles/{circle}', [CircleActionController::class, 'update'])->name('circles.update')->whereNumber('circle');
    Route::put('/circles/{circle}/settings', [CircleActionController::class, 'updateSettings'])->name('circles.update-settings')->whereNumber('circle');
    Route::delete('/circles/{circle}', [CircleActionController::class, 'destroy'])->name('circles.destroy')->whereNumber('circle');
    Route::post('/circles/{circle}/members', [CircleActionController::class, 'addMember'])->name('circles.members.store')->whereNumber('circle');
    Route::post('/circle-invitations/{invitation}/accept', [CircleActionController::class, 'acceptInvitation'])->name('circle-invitations.accept')->whereNumber('invitation');
    Route::post('/circle-invitations/{invitation}/decline', [CircleActionController::class, 'declineInvitation'])->name('circle-invitations.decline')->whereNumber('invitation');
    Route::delete('/circles/{circle}/invitations/{invitation}', [CircleActionController::class, 'cancelInvitation'])->name('circles.invitations.destroy')->whereNumber(['circle', 'invitation']);
    Route::delete('/circles/{circle}/members/{user}', [CircleActionController::class, 'removeMember'])->name('circles.members.destroy');
    Route::post('/circles/{circle}/leave', [CircleActionController::class, 'leave'])->name('circles.leave')->whereNumber('circle');

    Route::get('/circles/{circle}/transfer-ownership', [CircleController::class, 'transferOwnership'])->name('circles.transfer-ownership')->whereNumber('circle');
    Route::post('/circles/{circle}/ownership-transfer', [CircleActionController::class, 'initiateOwnershipTransfer'])->name('circles.ownership-transfer.store')->whereNumber('circle');
    Route::delete('/circles/{circle}/ownership-transfer/{transfer}', [CircleActionController::class, 'cancelOwnershipTransfer'])->name('circles.ownership-transfer.destroy')->whereNumber(['circle', 'transfer']);
    Route::post('/circle-ownership-transfers/{transfer}/accept', [CircleActionController::class, 'acceptOwnershipTransfer'])->name('circle-ownership-transfers.accept')->whereNumber('transfer');
    Route::post('/circle-ownership-transfers/{transfer}/decline', [CircleActionController::class, 'declineOwnershipTransfer'])->name('circle-ownership-transfers.decline')->whereNumber('transfer');
});
