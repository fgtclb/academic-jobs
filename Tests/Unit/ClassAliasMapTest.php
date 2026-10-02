<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Unit;

use FGTCLB\AcademicBase\Form\FlashMessageCreationMode;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The deprecated class names of `Migrations/Code/ClassAliasMap.php`, which the class
 * alias loader of TYPO3 resolves in composer mode and TYPO3 itself in classic mode.
 *
 * `FlashMessageCreationMode` moved to EXT:academic_base in 3.0. A listener of
 * `AfterSaveJobEvent` in project code may still name it by its old name, so that name
 * has to resolve to the very same enum, not to a copy of it: a case of a copy would
 * be refused by the typed setter of the event.
 */
final class ClassAliasMapTest extends UnitTestCase
{
    private const OLD_FLASH_MESSAGE_CREATION_MODE = 'FGTCLB\\AcademicJobs\\SaveForm\\FlashMessageCreationMode';

    #[Test]
    public function everyDeprecatedNameResolvesToItsTarget(): void
    {
        $map = require __DIR__ . '/../../Migrations/Code/ClassAliasMap.php';
        $this->assertIsArray($map);
        $this->assertNotSame([], $map);
        foreach ($map as $aliasName => $className) {
            $this->assertTrue(class_exists($aliasName), sprintf('"%s" does not resolve.', $aliasName));
            $this->assertSame($className, (new \ReflectionClass($aliasName))->getName());
        }
    }

    #[Test]
    public function theOldEnumNameNamesTheEnumOfAcademicBase(): void
    {
        $this->assertTrue(enum_exists(self::OLD_FLASH_MESSAGE_CREATION_MODE));
        $this->assertSame(FlashMessageCreationMode::ALWAYS, constant(self::OLD_FLASH_MESSAGE_CREATION_MODE . '::ALWAYS'));
        $this->assertInstanceOf(self::OLD_FLASH_MESSAGE_CREATION_MODE, FlashMessageCreationMode::NEVER);
    }

    /**
     * A listener written against the old name, as a project wrote it for 2.x.
     */
    #[Test]
    public function aListenerUsingTheOldEnumNameStillChangesTheMode(): void
    {
        $event = new AfterSaveJobEvent(
            request: new ServerRequest(),
            job: new Job(),
            currentPageId: 10,
            settings: [],
            flashMessageCreationMode: FlashMessageCreationMode::default(),
            redirectPageId: null,
        );

        $event->setFlashMessageCreationMode(constant(self::OLD_FLASH_MESSAGE_CREATION_MODE . '::NEVER'));

        $this->assertSame(FlashMessageCreationMode::NEVER, $event->getFlashMessageCreationMode());
        $this->assertInstanceOf(self::OLD_FLASH_MESSAGE_CREATION_MODE, $event->getFlashMessageCreationMode());
    }
}
