<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs;

use FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry as SettingsRegistry;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'fgtclb/academic-jobs';
    }

    public function getFactories(): array
    {
        return [
            'academic-jobs.settings' => static::getSettings(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProvider::class => static::addEventListeners(...),
        ] + parent::getExtensions();
    }

    public static function getSettings(ContainerInterface $container): \Closure
    {
        return static function (BootCompletedEvent $event) use ($container): void {
            $settingsRegistry = $container->get(SettingsRegistry::class);
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(BootCompletedEvent::class, 'academic-jobs.settings');
        return $listenerProvider;
    }
}
