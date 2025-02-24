<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\SaveForm;

enum FlashMessageCreationMode: int
{
    case SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE = 0;
    case ALWAYS = 1;
    case NEVER = 2;

    public static function default(): self
    {
        return self::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE;
    }

    /**
     * - self::ALWAYS returns true
     * - self::NEVER returns false
     * - self::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE returns true redirectPage is not set or the current page,
     *   otherwise false is returned (to suppress creation of FlashMessages)
     */
    public function shouldBeCreated(int $currentPageId, ?int $redirectPageId = null): bool
    {
        $shouldRedirect = ($redirectPageId !== null && $redirectPageId > 0 && $currentPageId !== $redirectPageId);
        return match($this) {
            self::ALWAYS => true,
            self::NEVER => false,
            self::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE => $shouldRedirect === false,
        };
    }
}
