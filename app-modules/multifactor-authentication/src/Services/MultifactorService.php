<?php

namespace AdvisingApp\MultifactorAuthentication\Services;

use Closure;
use App\Models\User;
use BaconQrCode\Writer;
use Filament\Facades\Filament;
use Illuminate\Cache\Repository;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

class MultifactorService
{
    protected $twoFactorAuthentication;

    protected $forceTwoFactorAuthentication;

    protected $twoFactorRouteAction;

    public function __construct(
        protected Google2FA $engine,
        protected ?Repository $cache = null
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    // public function enableTwoFactorAuthentication(bool $condition = true, bool $force = false, string|Closure|array|null $action = TwoFactorPage::class)
    // {
    //     $this->twoFactorAuthentication = $condition;
    //     $this->forceTwoFactorAuthentication = $force;
    //     $this->twoFactorRouteAction = $action;

    //     return $this;
    // }

    // public function getForceTwoFactorAuthentication(): bool
    // {
    //     return $this->forceTwoFactorAuthentication;
    // }

    // public function getTwoFactorRouteAction(): string|Closure|array|null
    // {
    //     return $this->twoFactorRouteAction;
    // }

    public function getEngine()
    {
        return $this->engine;
    }

    public function generateSecretKey()
    {
        return $this->engine->generateSecretKey();
    }

    public function getTwoFactorQrCodeSvg(string $url)
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(150, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd()
            )
        ))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    public function getQrCodeUrl($companyName, $companyEmail, $secret)
    {
        return $this->engine->getQRCodeUrl($companyName, $companyEmail, $secret);
    }

    public function verify(string $code, ?User $user = null)
    {
        if (is_null($user)) {
            $user = Filament::auth()->user();
        }

        $secret = decrypt($user->two_factor_secret);

        $timestamp = $this->engine->verifyKeyNewer(
            $secret,
            $code,
            optional($this->cache)->get($key = 'mfa_codes.' . md5($code))
        );

        if ($timestamp !== false) {
            optional($this->cache)->put($key, $timestamp, ($this->engine->getWindow() ?: 1) * 60);

            return true;
        }

        return false;
    }

    public function shouldForceTwoFactor(): bool
    {
        // TODO: Needs to check if they are a non sso user
        return false;
        // if (filament()->getCurrentPanel()->isEmailVerificationRequired()) {
        //     return $this->forceTwoFactorAuthentication && ! filament()->auth()->user()?->hasConfirmedTwoFactor() && filament()->auth()->user()?->hasVerifiedEmail();
        // }

        // return $this->forceTwoFactorAuthentication && ! filament()->auth()->user()?->hasConfirmedTwoFactor();
    }
}
