<?php

namespace App\Providers;

use App\Models\InductionPolicy;
use App\Models\PortalUserNotification;
use App\Services\OceanicSmtpCredentialsService;
use App\Support\PortalCapability;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    protected static function normalizeSmtpPortForDsn(mixed $port): ?int
    {
        if ($port === null || $port === '') {
            return null;
        }

        $validated = filter_var($port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        return $validated === false ? null : $validated;
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('oceanic_smtp', function (array $config) {
            $merged = $config;

            app(OceanicSmtpCredentialsService::class)->applyToMailConfig($merged);

            $port = self::normalizeSmtpPortForDsn($merged['port'] ?? null);

            $factory = new EsmtpTransportFactory;
            $scheme = $merged['scheme'] ?? null;
            if (! $scheme) {
                $scheme = (($port ?? 587) === 465) ? 'smtps' : 'smtp';
            }

            $dsnOptions = array_merge(
                Arr::except($merged, ['transport', 'name']),
                ['verify_peer' => (bool) config('oceanic.smtp_verify_peer', true)]
            );

            $transport = $factory->create(new Dsn(
                $scheme,
                (string) $merged['host'],
                $merged['username'] ?? null,
                $merged['password'] ?? null,
                $port,
                $dsnOptions
            ));

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream && isset($merged['timeout'])) {
                $stream->setTimeout($merged['timeout']);
            }

            return $transport;
        });

        View::composer('*', function ($view): void {
            $request = request();
            if (! $request->attributes->has('portal_cap')) {
                $request->attributes->set('portal_cap', PortalCapability::forUser(Auth::user()));
            }
            $view->with('portalCap', $request->attributes->get('portal_cap'));
        });

        View::composer('components.portal-policies-nav', function ($view): void {
            $cap = PortalCapability::forUser(Auth::user());
            if (! ($cap->inductionAdminAccess ?? false)) {
                $view->with('sidebarPolicies', collect());

                return;
            }

            $view->with(
                'sidebarPolicies',
                InductionPolicy::query()->ordered()->get(['id', 'name', 'abbreviation']),
            );
        });

        View::composer('layouts.portal', function ($view): void {
            $user = Auth::user();
            if ($user === null) {
                return;
            }

            $base = PortalUserNotification::query()->where('user_id', $user->id);
            $view->with('portalHeaderNotifications', (clone $base)->orderByDesc('created_at')->limit(10)->get());
            $view->with('portalUnreadNotificationCount', (clone $base)->whereNull('read_at')->count());
        });
    }
}
