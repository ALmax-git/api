<?php

namespace App\Enums;

/**
 * Defines the lifecycle status of an application in the X-Store.
 */
enum AppStatus: string
{
    // App is still being built or configured, not ready for submission.
    case DRAFT = 'draft';

        // Developer has submitted the app, pending ALmax approval.
    case IN_REVIEW = 'in_review';

        // Live and visible in the X-Store (unless restricted by OS version).
    case PUBLISHED = 'published';

        // App has been temporarily or permanently removed due to violations or issues.
    case SUSPENDED = 'suspended';

        // App is no longer maintained but might still be usable by existing users.
    case DEPRECATED = 'deprecated';

    /**
     * Get a human-readable label for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'In Review',
            self::PUBLISHED => 'Published',
            self::SUSPENDED => 'Suspended',
            self::DEPRECATED => 'Deprecated',
        };
    }

    /**
     * Get a status color (e.g., tailored for Tailwind CSS classes) for UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'text-gray-600 bg-gray-100',
            self::IN_REVIEW => 'text-yellow-700 bg-yellow-100',
            self::PUBLISHED => 'text-green-700 bg-green-100',
            self::SUSPENDED => 'text-red-700 bg-red-100',
            self::DEPRECATED => 'text-orange-700 bg-orange-100',
        };
    }
}
