<?php

namespace App\Enums;

enum Languages: string
{
    case Arabic         = 'ar';
    case Chinese        = 'zh';
    case English        = 'en';
    case French         = 'fr';
    case Hausa          = 'ha';
    case Hindi          = 'hi';
    case Igbo           = 'ig';
    case NigerianPidgin = 'pcm';
    case Spanish        = 'es';
    case Urdu           = 'ur';
    case Yoruba         = 'yo';

    /**
     * Emoji flag for the dropdown.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Arabic         => '🇸🇦',
            self::Chinese        => '🇨🇳',
            self::English        => '🇬🇧',
            self::French         => '🇫🇷',
            self::Hausa          => '🇳🇬',
            self::Hindi          => '🇮🇳',
            self::Igbo           => '🇳🇬',
            self::NigerianPidgin => '🇳🇬',
            self::Spanish        => '🇪🇸',
            self::Urdu           => '🇵🇰',
            self::Yoruba         => '🇳🇬',
        };
    }

    /**
     * Currency typically used in regions where the language is spoken.
     */
    public function currency(): string
    {
        return match ($this) {
            self::Arabic         => 'SAR',
            self::Chinese        => 'CNY',
            self::English        => 'USD',
            self::French         => 'EUR',
            self::Hausa          => 'NGN',
            self::Hindi          => 'INR',
            self::Igbo           => 'NGN',
            self::NigerianPidgin => 'NGN',
            self::Spanish        => 'EUR',
            self::Urdu           => 'PKR',
            self::Yoruba         => 'NGN',
        };
    }

    /**
     * Human‑readable English name (used in Auth::user()->language checks).
     */
    public function display(): string
    {
        return match ($this) {
            self::Arabic         => 'Arabic',
            self::Chinese        => 'Chinese',
            self::English        => 'English',
            self::French         => 'French',
            self::Hausa          => 'Hausa',
            self::Hindi          => 'Hindi',
            self::Igbo           => 'Igbo',
            self::NigerianPidgin => 'Nigerian Pidgin',
            self::Spanish        => 'Spanish',
            self::Urdu           => 'Urdu',
            self::Yoruba         => 'Yoruba',
        };
    }

    /**
     * The language’s own name (endonym).
     */
    public function originName(): string
    {
        return match ($this) {
            self::Arabic         => 'العربية',
            self::Chinese        => '中文',
            self::English        => 'English',
            self::French         => 'Français',
            self::Hausa          => 'Hausa',
            self::Hindi          => 'हिन्दी',
            self::Igbo           => 'Igbo',
            self::NigerianPidgin => 'Naijá',
            self::Spanish        => 'Español',
            self::Urdu           => 'اردو',
            self::Yoruba         => 'Yorùbá',
        };
    }

    /**
     * ISO 639-1 two‑letter code.
     */
    public function iso2(): string
    {
        return $this->value;
    }

    /**
     * ISO 639-3 three‑letter code.
     */
    public function iso3(): string
    {
        return match ($this) {
            self::Arabic         => 'ara',
            self::Chinese        => 'zho',
            self::English        => 'eng',
            self::French         => 'fra',
            self::Hausa          => 'hau',
            self::Hindi          => 'hin',
            self::Igbo           => 'ibo',
            self::NigerianPidgin => 'pcm',
            self::Spanish        => 'spa',
            self::Urdu           => 'urd',
            self::Yoruba         => 'yor',
        };
    }

    /**
     * Full locale string (e.g., 'en_US', 'ar_SA').
     */
    public function locale(): string
    {
        return match ($this) {
            self::Arabic         => 'ar_SA',
            self::Chinese        => 'zh_CN',
            self::English        => 'en_US',
            self::French         => 'fr_FR',
            self::Hausa          => 'ha_NG',
            self::Hindi          => 'hi_IN',
            self::Igbo           => 'ig_NG',
            self::NigerianPidgin => 'pcm_NG',
            self::Spanish        => 'es_ES',
            self::Urdu           => 'ur_PK',
            self::Yoruba         => 'yo_NG',
        };
    }

    /**
     * Retrieve a case from its display() string.
     */
    public static function fromDisplay(string $display): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->display() === $display) {
                return $case;
            }
        }
        return null;
    }
}
