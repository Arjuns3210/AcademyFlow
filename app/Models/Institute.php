<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Institute extends Model
{
    use HasFactory, HasStatus;

    protected $fillable = [
        'institute_type_id',
        'name',
        'logo',
        'theme_color',
        'address',
        'contact',
        'email',
        'gst_number',
        'status',
    ];

    public function instituteType(): BelongsTo
    {
        return $this->belongsTo(InstituteType::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'tenant_id');
    }

    public function instituteModules(): HasMany
    {
        return $this->hasMany(InstituteModule::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Modules enabled for this institute, with their custom label (if any).
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'institute_modules')
            ->withPivot('custom_label', 'is_enabled')
            ->withTimestamps();
    }

    /**
     * The user holding this institute's non-deletable "Institute Admin"
     * system role. Null if that role/user hasn't been provisioned yet.
     */
    public function adminUser(): ?User
    {
        return $this->users()->whereHas('roleRecord', function ($query) {
            $query->where('name', 'Institute Admin')->where('is_system', true);
        })->first();
    }

    /**
     * This institute's display label for a module (e.g. "Dance Masters"
     * instead of "Teachers") — for page titles/breadcrumbs outside the
     * sidebar, which already handles this via InstituteModule::label().
     * Falls back to a title-cased slug in the unexpected case that the
     * module isn't enabled/found for this institute at all.
     */
    public function moduleLabel(string $slug): string
    {
        $instituteModule = $this->instituteModules()
            ->whereHas('module', fn ($query) => $query->where('slug', $slug))
            ->with('module')
            ->first();

        return $instituteModule?->label() ?? Str::headline($slug);
    }

    /**
     * Short code derived from this institute's name, e.g. "Bright Coaching
     * Classes" -> "BC" (first letter of each of the first two words).
     * Single-word names fall back to that word's first 2-3 letters, e.g.
     * "Vidyalaya" -> "VID". Used as a prefix for per-tenant sequences like
     * student codes and fee receipt numbers.
     */
    public function shortCode(): string
    {
        $words = collect(preg_split('/\s+/', trim($this->name)))->filter()->values();

        $raw = $words->count() >= 2
            ? $words->take(2)->map(fn ($word) => Str::substr($word, 0, 1))->implode('')
            : Str::substr($words->first() ?? '', 0, 3);

        $code = Str::upper(preg_replace('/[^A-Za-z]/', '', $raw));

        return $code !== '' ? $code : 'ST';
    }

    /**
     * Inline CSS custom properties for the tenant sidebar, e.g.
     * `--sidebar-bg: #1a2b3c; --sidebar-text: rgba(255,255,255,.75); ...`.
     * Falls back to the stock dark-sidebar values (matching Bootstrap's
     * $dark exactly) when no custom color is set, so an institute that
     * never picks one sees zero visual change from before this existed.
     *
     * Also switches the text tone based on the chosen color's perceived
     * brightness — a light theme color needs dark text, not white-on-white.
     * This uses a simple brightness heuristic (ITU-R BT.601), not full WCAG
     * relative luminance — good enough to avoid illegible text, not an
     * accessibility-compliance calculation.
     */
    public function sidebarStyle(): string
    {
        $background = $this->theme_color ?: '#212529';

        // Dark text at a given opacity reads noticeably weaker against a
        // pale pastel background than light text at the same opacity does
        // against near-black — the two aren't visually symmetric, so the
        // light-background case needs a stronger opacity to look equally
        // legible, not just the mirror of the dark case.
        $text = $this->hasLightBackground() ? 'rgba(0, 0, 0, .85)' : 'rgba(255, 255, 255, .75)';
        $textStrong = $this->hasLightBackground() ? '#000' : '#fff';

        return "--sidebar-bg: {$background}; --sidebar-text: {$text}; --sidebar-text-strong: {$textStrong};";
    }

    /**
     * Exposed separately from sidebarStyle() so the layout can also toggle
     * a font-weight class — a light theme needs more than just a stronger
     * opacity to read as clearly as the default dark sidebar does.
     */
    public function hasLightBackground(): bool
    {
        return (bool) ($this->theme_color && $this->isLightColor($this->theme_color));
    }

    private function isLightColor(string $hex): bool
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return false;
        }

        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $brightness > 150;
    }
}
