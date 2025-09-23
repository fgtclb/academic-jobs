<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Controller;

use FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Extbase\Property\TypeConverter\FileUploadConverter;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Domain\Validator\JobValidator;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent;
use FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry;
use FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class JobController extends ActionController
{
    use GetSelectItemsForTcaManagedTableFieldMethodTrait;

    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ImageService $imageService,
        private readonly LocalizationUtility $localizationUtility,
        protected readonly BackendUriBuilder $backendUriBuilder,
        protected AcademicJobsSettingsRegistry $settingsRegistry,
    ) {}

    public function listAction(): ResponseInterface
    {
        $jobType = $this->settings['job']['type'] ?? 0;

        if ($jobType > 0) {
            $jobs = $this->jobRepository->findByJobType((int)$jobType);
        } else {
            $jobs = $this->jobRepository->findAll();
        }

        $this->view->assignMultiple([
            'jobs' => $jobs,
            'data' => $this->getCurrentContentObjectRenderer()?->data,
        ]);

        return $this->htmlResponse();
    }

    public function showAction(?Job $job = null): ResponseInterface
    {
        if ($job === null) {
            $this->addFlashMessage(
                $this->translateAlert('job_not_found.body', 'Job not found.'),
                '',
                ContextualFeedbackSeverity::ERROR,
                true
            );
            return $this->htmlResponse();
        }

        $title = $job->getTitle();
        $description = strip_tags($job->getDescription());
        $image = $this->getImageUri($job->getImage());

        /** @var array<string, string> */
        $metaTags = [
            'title' => $title,
            'description' => $description,
            'og:title' => $title,
            'og:description' => $description,
            'twitter:card' => 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];

        if ($image !== null) {
            $metaTags['og:image'] = $image;
            $metaTags['og:image:alt'] = $title;
            $metaTags['twitter:image'] = $image;
            $metaTags['twitter:image:alt'] = $title;
        }

        $this->setMetaTags($metaTags);

        $this->view->assignMultiple([
            'job' => $job,
            'data' => $this->getCurrentContentObjectRenderer()?->data,
        ]);

        return $this->htmlResponse();
    }

    public function newAction(): ResponseInterface
    {
        $pluginControllerActionContext = new PluginControllerActionContext(
            request: $this->request,
            settings: $this->settings,
        );
        $this->view->assignMultiple([
            'employmentTypeOptions' => $this->getSelectItemsForTcaManagedTableField(
                $this->request,
                $this->localizationUtility,
                'academic_jobs',
                'tx_academicjobs_domain_model_job',
                'employment_type',
                [''],
            ),
            'typeOptions' => $this->getSelectItemsForTcaManagedTableField(
                $this->request,
                $this->localizationUtility,
                'academic_jobs',
                'tx_academicjobs_domain_model_job',
                'type',
                [''],
            ),
            'data' => $this->getCurrentContentObjectRenderer()?->data,
        ]);

        // As an object is passed to this event and objects are passed by reference in PHP,
        // the event listener can modify the view object without needing to assign it afterwards.
        // Trying to assign it back to the view would break the dual-version support.
        $this->eventDispatcher->dispatch(new ModifyJobControllerNewActionViewEvent(
            pluginControllerActionContext: $pluginControllerActionContext,
            view: $this->view,
        ));

        // Additional variables which should not be able to be manipulated by the event
        $this->view->assignMultiple(
            [
                'validations' => $this->settingsRegistry->getValidationsForFrontend('job'),
            ]
        );
        return $this->htmlResponse();
    }

    public function initializeCreateAction(): void
    {
        if ($this->request->hasArgument('job')) {
            $jobArgumentConfiguration = $this->arguments->getArgument('job')->getPropertyMappingConfiguration();

            $propertiesToConvert = [
                'employmentStartDate' => 'Y-m-d',
                'starttime' => 'Y-m-d',
                'endtime' => 'Y-m-d',
            ];

            foreach ($propertiesToConvert as $propertyToConvert => $format) {
                $jobArgumentConfiguration->forProperty($propertyToConvert)
                    ->setTypeConverterOption(
                        DateTimeConverter::class,
                        DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                        $format
                    );
            }

            GeneralUtility::makeInstance(FileUploadConverter::class)
                ->setArgumentTypeConverterConfiguration(
                    $this->arguments,
                    'job',
                    'image',
                    [
                        FileUploadConverter::CONFIGURATION_UPLOAD_FOLDER => $this->settings['jobAvatarImage']['uploadFolder'] ?? '1:user_upload/',
                        FileUploadConverter::CONFIGURATION_VALIDATION_FILESIZE_MAXIMUM => $this->settings['jobAvatarImage']['validation']['fileSize']['maximum'] ?? PHP_INT_MAX . 'B',
                        FileUploadConverter::CONFIGURATION_VALIDATION_MIME_TYPE_ALLOWED_MIME_TYPES => $this->settings['jobAvatarImage']['validation']['mimeType']['allowedMimeTypes'] ?? null,
                    ],
                );
        }
    }

    /**
     * @todo It's not a really good practice to use persisting extbase models directly, this should be a DTO object,
     *       see `EXT:academic_persons_edit` for examples.
     */
    #[Validate([
        'param' => 'job',
        'validator' => JobValidator::class,
    ])]
    public function createAction(?Job $job = null): ResponseInterface
    {
        if ($job === null) {
            return $this->redirect('new');
        }

        $job->setHidden(1);
        $this->jobRepository->add($job);
        $this->persistenceManager->persistAll();

        $uid = $job->getUid();
        if ($uid === null) {
            $this->addFlashMessage(
                $this->translateAlert('job_not_created.body', 'Something went wrong.'),
                $this->translateAlert('job_not_created.title', 'Job not created'),
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $this->redirect('new');
        }

        $currentPageId = $this->determineCurrentPageId();
        $redirectPageId = $this->resolveRedirectPageId();
        $flashMessageCreationMode = $this->resolveFlashMessageCreationMode();

        $afterSaveJobEvent = new AfterSaveJobEvent(
            request: $this->request,
            job: $job,
            currentPageId: $currentPageId,
            settings: $this->settings,
            flashMessageCreationMode: $flashMessageCreationMode,
            redirectPageId: $redirectPageId
        );
        /** @var AfterSaveJobEvent $afterSaveJobEvent */
        $afterSaveJobEvent = $this->eventDispatcher->dispatch($afterSaveJobEvent);

        $redirectPageId = $afterSaveJobEvent->getRedirectPageId();
        $flashMessageCreationMode = $afterSaveJobEvent->getFlashMessageCreationMode();
        $listPid = $this->settings['listPid'] ? (int)$this->settings['listPid'] : null;
        $mailWasSent = $this->sendEmail($uid);

        $useRedirectPageId = $redirectPageId;
        if ($useRedirectPageId === null && $listPid !== null && $listPid > 0) {
            trigger_error(
                sprintf(
                    'Using TypoScript setting "%s" to redirect after persisting new job is deprecated.'
                    . ' Use NewJobForm plugin setting redirectPageId or TypoScript "%s" instead.',
                    'plugin.tx_academicjobs.settings.listPid',
                    'plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId'
                ),
                E_USER_DEPRECATED
            );
            $useRedirectPageId = $listPid;
        }

        // FlashMessage are added to a queue based on default extbase identifier determination. Creating them in the
        // session for the current form page and redirecting to another page would not consume the FlashMessages without
        // enforcing a concrete matching identifier. If target page is already fully cached and no USER_INT elements is
        // placed on that page, they will not be consumed. When user navigates back to the new form page will display
        // the FlashMessage which is highly confusing to casual website visitors.
        //
        // Based on the above reasoning, we allow to configure the behaviour in installation per plugin instance with
        // fallback to a global TypoScript setting to determine if flash messages should be created
        //   - ALWAYS
        //   - NEVER
        //   - SUPPRESS_WHEN_REDIRECTED (only when staying on the same page)
        //
        // Not that this still requires to have a uncached conent element rendering the specific newjobform plugin
        // extbase flash message queue, for example using following fluid code:
        //
        // ```xml
        // <f:flashMessages queueIdentifier="extbase.flashmessages.tx_academicjobs_newjobform"/>
        // ```
        // @todo Make flashmessage queue identifier configurable or at least use a dedicated identifer when redirecting.
        if ($flashMessageCreationMode->shouldBeCreated($currentPageId, $useRedirectPageId)) {
            if ($mailWasSent) {
                $this->addFlashMessageToQueue(
                    $this->translateAlert('job_created.body', 'Job created and email sent.'),
                    $this->translateAlert('job_created.title', 'Job created'),
                    ContextualFeedbackSeverity::OK,
                    true
                );
            } else {
                $this->addFlashMessageToQueue(
                    $this->translateAlert('job_created_no_email.body', 'Job created, but email could not be sent.'),
                    $this->translateAlert('job_created_no_email.title', 'Job created'),
                    ContextualFeedbackSeverity::WARNING,
                    true
                );
            }
        }

        if ($useRedirectPageId !== null) {
            $uri = $this->uriBuilder->setTargetPageUid($useRedirectPageId)->build();
            // Since TYPO3v12 redirect method returns a response object. Return it directly.
            return $this->redirectToUri($uri);
        }
        // Since TYPO3v12 redirect method returns a response object. Return it directly.
        return $this->redirect('list');
    }

    /**
     * ------------------------------------------------------------------------
     * Helper functions
     * ------------------------------------------------------------------------
     */

    /**
     * @param FileReference|null $imageObject
     */
    public function getImageUri($imageObject): ?string
    {
        if ($imageObject === null) {
            return null;
        }
        $originalResource = $imageObject->getOriginalResource();

        return $this->imageService->getImageUri($originalResource, true);
    }

    /**
     * @param array<string, string> $metaTags
     */
    private function setMetaTags(array $metaTags): void
    {
        $metaTagManager = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);

        foreach ($metaTags as $property => $content) {
            $metaTagManagerForProperty = $metaTagManager->getManagerForProperty($property);
            $metaTagManagerForProperty->addProperty($property, $content);
        }
    }

    public function sendEmail(int $recordId): bool
    {
        $url = $this->buildUrl($recordId);

        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail->to($this->settings['email']['recipientEmail']);
        $mail->from($this->settings['email']['senderEmail']);
        $mail->subject($this->settings['email']['subject']);
        $mail->text('A new job has been posted. Please check the TYPO3 backend: ' . $url);

        return $mail->send();
    }

    public function buildUrl(int $recordId): string
    {
        $path = $this->backendUriBuilder
            ->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'tx_academicjobs_domain_model_job' => [
                            $recordId => 'edit',
                        ],
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            );

        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $path;
    }

    private function translateAlert(
        string $alert,
        string $missing = 'Missing translation!'
    ): string {
        return LocalizationUtility::translate('tx_academicjobs.fe.alert.' . $alert, 'AcademicJobs') ?? $missing;
    }

    /**
     * Resolves redirect page id to use from different sources, using following priority order:
     *
     * 1. Plugin settings (flexform): redirectPageId
     * 2. TypoScript setting (SETUP/CONSTANTS): plugin.tx_academicjobs.saveForm.fallbackRedirectPageId
     * 3. return null indicating no external page redirect
     *
     * @return int|null
     */
    private function resolveRedirectPageId(): ?int
    {
        // Plugin flexform settings
        if (isset($this->settings['redirectPageId'])
            && (is_string($this->settings['redirectPageId']) || is_int($this->settings['redirectPageId']))
            && MathUtility::canBeInterpretedAsInteger($this->settings['redirectPageId'])
        ) {
            // Ensure positive page id
            $redirectPageId = (int)$this->settings['redirectPageId'];
            return ($redirectPageId > 0)
                ? $redirectPageId
                : null;
        }
        // TypoScript SETUP/CONSTANT
        if (isset($this->settings['saveForm'])
            && is_array($this->settings['saveForm'])
            && isset($this->settings['saveForm']['fallbackRedirectPageId'])
            && (is_string($this->settings['saveForm']['fallbackRedirectPageId']) || is_int($this->settings['saveForm']['fallbackRedirectPageId']))
            && MathUtility::canBeInterpretedAsInteger($this->settings['saveForm']['fallbackRedirectPageId'])
        ) {
            // Ensure positive page id
            $fallbackRedirectPageId = (int)$this->settings['saveForm']['fallbackRedirectPageId'];
            return ($fallbackRedirectPageId > 0)
                ? $fallbackRedirectPageId
                : null;
        }
        return null;
    }

    private function resolveFlashMessageCreationMode(): FlashMessageCreationMode
    {
        // Plugin flexform settings
        if (isset($this->settings['flashMessageCreationMode'])
            && (is_string($this->settings['flashMessageCreationMode']) || is_int($this->settings['flashMessageCreationMode']))
            && MathUtility::canBeInterpretedAsInteger($this->settings['flashMessageCreationMode'])
            && (int)$this->settings['flashMessageCreationMode'] >= 0
            && FlashMessageCreationMode::tryFrom((int)$this->settings['flashMessageCreationMode']) !== null
        ) {
            return FlashMessageCreationMode::from((int)$this->settings['flashMessageCreationMode']);
        }
        // TypoScript SETUP/CONSTANT
        if (isset($this->settings['saveForm'])
            && is_array($this->settings['saveForm'])
            && isset($this->settings['saveForm']['fallbackFlashMessageCreationMode'])
            && (is_string($this->settings['saveForm']['fallbackFlashMessageCreationMode']) || is_int($this->settings['saveForm']['fallbackFlashMessageCreationMode']))
            && MathUtility::canBeInterpretedAsInteger($this->settings['saveForm']['fallbackFlashMessageCreationMode'])
            && (int)$this->settings['saveForm']['fallbackFlashMessageCreationMode'] >= 0
            && FlashMessageCreationMode::tryFrom((int)$this->settings['saveForm']['fallbackFlashMessageCreationMode']) !== null
        ) {
            return FlashMessageCreationMode::from((int)$this->settings['saveForm']['fallbackFlashMessageCreationMode']);
        }
        return FlashMessageCreationMode::default();
    }

    /**
     * Determine current displayed page id. Works only when used in FE context.
     */
    private function determineCurrentPageId(): int
    {
        $frontendController = $this->request->getAttribute('frontend.controller');
        if ($frontendController instanceof TypoScriptFrontendController) {
            return (int)$frontendController->id;
        }
        return 0;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue specified by `$queueIdentifier`.
     *
     * Adopted from {@see parent::addFlashMessage()} making the queue identifier configurable.
     */
    private function addFlashMessageToQueue(
        string $messageBody,
        string $messageTitle = '',
        ?ContextualFeedbackSeverity $severity = null,
        bool $storeInSession = true,
        ?string $queueIdentifier = null,
    ): void {
        $severity ??= ContextualFeedbackSeverity::OK;
        if ($queueIdentifier === null) {
            $this->addFlashMessage($messageBody, $messageTitle, $severity, $storeInSession);
            return;
        }

        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The message body must be of type string, "%s" given.',
                    gettype($messageBody)
                ),
                1740480922
            );
        }
        if ($queueIdentifier === '') {
            throw new \InvalidArgumentException(
                'The queue identifier must be set but was an empty string.',
                1740481093
            );
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageBody,
            $messageTitle,
            $severity,
            $storeInSession
        );

        $this->getFlashMessageQueue($queueIdentifier)->enqueue($flashMessage);
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
