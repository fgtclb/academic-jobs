<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Form\AfterSaveResolver;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Domain\Validator\JobValidator;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use FGTCLB\AcademicJobs\Settings\AcademicJobsSettings;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\InvalidArgumentException as MimeInvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Exception as FluidException;

final class JobController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;
    use GetSelectItemsForTcaManagedTableFieldMethodTrait;

    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ImageService $imageService,
        private readonly LocalizationUtility $localizationUtility,
        protected readonly BackendUriBuilder $backendUriBuilder,
        private readonly AcademicJobsSettings $academicJobsSettings,
        protected readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly AfterSaveResolver $afterSaveResolver,
    ) {}

    public function listAction(): ResponseInterface
    {
        $context = new PluginControllerActionContext($this->request, $this->settings);
        $jobType = $this->settings['job']['type'] ?? 0;
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);

        if ($jobType > 0) {
            $jobs = $this->jobRepository->findByJobType((int)$jobType, $showHiddenRecords);
        } else {
            $jobs = $this->jobRepository->findAllJobs($showHiddenRecords);
        }

        $this->view->assignMultiple([
            'jobs' => $jobs,
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
        ]);
        $this->assignPagination($jobs, $this->requestedPage());
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * The page a pagination link asked for, read here rather than as an action argument:
     * a value that is no integer would fail the argument validation and answer every
     * request to the list with an error, whether it is paginated or not. Such a value, and
     * one below one, is the first page. The paginator shows the last page for one beyond it.
     */
    private function requestedPage(): int
    {
        if (!$this->request->hasArgument('currentPage')) {
            return 1;
        }
        $currentPage = $this->request->getArgument('currentPage');
        if (!MathUtility::canBeInterpretedAsInteger($currentPage)) {
            return 1;
        }

        return max(1, (int)$currentPage);
    }

    /**
     * Splits the list into pages when the content element enables it, the way the partner
     * list of `academic_partners` does: numbered page links when `numbered_pagination` is
     * loaded, the core pagination otherwise. The paginator refuses fewer than one result
     * per page, and the numbered pagination would take ten links for fewer than one, so
     * both fall back to the default of their field.
     *
     * @param QueryResultInterface<int, Job> $jobs
     */
    private function assignPagination(QueryResultInterface $jobs, int $currentPage): void
    {
        if (!(bool)($this->settings['paginationEnabled'] ?? false)) {
            return;
        }
        $resultsPerPage = (int)($this->settings['pagination']['resultsPerPage'] ?? 0);
        $numberOfLinks = (int)($this->settings['pagination']['numberOfLinks'] ?? 0);

        $paginator = new QueryResultPaginator(
            $jobs,
            $currentPage,
            $resultsPerPage > 0 ? $resultsPerPage : 10,
        );
        if (ExtensionManagementUtility::isLoaded('numbered_pagination')
            && class_exists(NumberedPagination::class)
        ) {
            $pagination = new NumberedPagination($paginator, $numberOfLinks > 0 ? $numberOfLinks : 5);
        } else {
            $pagination = new SimplePagination($paginator);
        }

        $this->view->assignMultiple([
            'paginator' => $paginator,
            'pagination' => $pagination,
        ]);
    }

    public function showAction(?Job $job = null): ResponseInterface
    {
        $context = new PluginControllerActionContext($this->request, $this->settings);
        // Assigned before the early return below: the template renders the
        // `EXT:fluid_styled_content` header partial in both cases, and on TYPO3 v14 that
        // partial resolves the header through `record`. Leaving it unassigned made a
        // request without a resolvable job fail with an exception instead of rendering the
        // "not found" message.
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
        ]);

        if ($job === null) {
            $this->addFlashMessage(
                $this->translateAlert('job_not_found.body', 'Job not found.'),
                '',
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);
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

        $this->view->assign('job', $job);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    public function newAction(): ResponseInterface
    {
        $context = new PluginControllerActionContext($this->request, $this->settings);
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
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
        ]);

        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        // Assigned after the event on purpose, so a listener cannot replace them.
        $this->view->assignMultiple(
            [
                'validations' => $this->academicJobsSettings->getValidationSet('job')->validations,
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

            $this->mapUncheckedFlagsToZero();
            $this->configureImageFileUpload();
            $this->addJobValidator();
        }
    }

    /**
     * An unchecked checkbox submits the empty value of the hidden field the checkbox view
     * helper renders with it, and Extbase converts an empty value to `null` for an `int`
     * property. The setters of both job flags take an `int`, so the empty value becomes
     * `0` before the arguments are mapped.
     */
    private function mapUncheckedFlagsToZero(): void
    {
        $job = $this->request->getArgument('job');
        if (!is_array($job)) {
            return;
        }
        foreach (['internationalsWelcome', 'alumniRecommend'] as $flag) {
            if (($job[$flag] ?? null) === '') {
                $job[$flag] = '0';
            }
        }
        $this->request = $this->request->withArgument('job', $job);
    }

    /**
     * Registers the native Extbase file upload handling for the job avatar image.
     *
     * The upload folder and both validation limits stay TypoScript driven
     * (`settings.jobAvatarImage.*`), which is why the configuration is built here
     * instead of using the static `#[FileUpload]` attribute on the domain model.
     */
    private function configureImageFileUpload(): void
    {
        $jobArgument = $this->arguments->getArgument('job');

        $fileUploadConfiguration = (new FileUploadConfiguration('image'))
            ->setMaxFiles(1)
            ->setUploadFolder(
                (string)($this->settings['jobAvatarImage']['uploadFolder'] ?? '1:/user_upload/')
            );

        $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
        $fileSizeValidator->setOptions([
            'maximum' => (string)($this->settings['jobAvatarImage']['validation']['fileSize']['maximum'] ?? PHP_INT_MAX . 'B'),
        ]);
        $fileUploadConfiguration->addValidator($fileSizeValidator);

        // An empty list means "no mime type restriction". `MimeTypeValidator` throws
        // for an empty `allowedMimeTypes` option, so it is only added when configured.
        $allowedMimeTypes = GeneralUtility::trimExplode(
            ',',
            (string)($this->settings['jobAvatarImage']['validation']['mimeType']['allowedMimeTypes'] ?? ''),
            true
        );
        if ($allowedMimeTypes !== []) {
            $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
            $mimeTypeValidator->setOptions(['allowedMimeTypes' => $allowedMimeTypes]);
            $fileUploadConfiguration->addValidator($mimeTypeValidator);
        }

        $jobArgument->getFileHandlingServiceConfiguration()
            ->addFileUploadConfiguration($fileUploadConfiguration);
        // The upload is handled by the file handling service, not by the property mapper.
        $jobArgument->getPropertyMappingConfiguration()->skipProperties('image');
    }

    /**
     * Adds the `JobValidator` to the validators Extbase already built for the
     * `job` argument.
     *
     * This is done programmatically instead of with a `#[Validate]` attribute,
     * because both attribute forms usable on TYPO3 v13 are deprecated on v14 and
     * will be removed in v15: passing an array of configuration values, and
     * naming the validated parameter. The documented replacement — placing the
     * attribute on the method parameter — requires `Attribute::TARGET_PARAMETER`,
     * which TYPO3 v13 does not declare, so it cannot be used while v13 is
     * supported. The API used here emits no deprecation on either version.
     *
     * `initializeActionMethodValidators()` runs before this method and already
     * put a `ConjunctionValidator` holding the base validation on the argument,
     * so it is extended rather than replaced — replacing it would silently drop
     * the model level validation.
     */
    private function addJobValidator(): void
    {
        $jobArgument = $this->arguments->getArgument('job');
        $jobValidator = $this->validatorResolver->createValidator(JobValidator::class);
        if ($jobValidator === null) {
            return;
        }

        $validator = $jobArgument->getValidator();
        if ($validator instanceof ConjunctionValidator) {
            $validator->addValidator($jobValidator);
            return;
        }

        /** @var ConjunctionValidator $conjunctionValidator */
        $conjunctionValidator = $this->validatorResolver->createValidator(ConjunctionValidator::class);
        if ($validator !== null) {
            $conjunctionValidator->addValidator($validator);
        }
        $conjunctionValidator->addValidator($jobValidator);
        $jobArgument->setValidator($conjunctionValidator);
    }

    /**
     * @todo It's not a really good practice to use persisting extbase models directly, this should be a DTO object,
     *       see `EXT:academic_persons_edit` for examples.
     */
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
        $redirectPageId = $this->afterSaveResolver->redirectPageId($this->settings);
        $flashMessageCreationMode = $this->afterSaveResolver->flashMessageCreationMode($this->settings);

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
        $mailWasSent = $this->sendEmail($uid, $job);

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
        $afterSaveDecision = $this->afterSaveResolver->decide($currentPageId, $useRedirectPageId, $flashMessageCreationMode);
        if ($afterSaveDecision->createFlashMessage) {
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

        if ($afterSaveDecision->redirectPageId !== null) {
            $uri = $this->uriBuilder->setTargetPageUid($afterSaveDecision->redirectPageId)->build();
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

    /**
     * Announces a submitted job with the mail template `settings.email.templateName`. The
     * template renders the text of `settings.email.template`, or its own translated default
     * message when that is empty.
     *
     * The job is already saved, so a mail that cannot be sent is logged rather than ending
     * the request: the addresses are checked while the mail is built, the template is
     * rendered while it is sent. Any other error is a defect and is not caught.
     */
    private function sendEmail(int $recordId, Job $job): bool
    {
        $templateName = trim((string)($this->settings['email']['templateName'] ?? ''));

        try {
            $mail = $this->createEmail()
                ->setTemplate($templateName !== '' ? $templateName : 'JobCreated')
                ->to($this->settings['email']['recipientEmail'])
                ->from($this->settings['email']['senderEmail'])
                ->subject($this->settings['email']['subject'])
                ->assignMultiple([
                    'job' => $job,
                    'url' => $this->buildUrl($recordId),
                    'settings' => $this->settings,
                    'emailText' => trim((string)($this->settings['email']['template'] ?? '')),
                ]);

            $this->mailer->send($mail);
        } catch (RfcComplianceException|MimeInvalidArgumentException|TransportExceptionInterface|FluidException $exception) {
            $this->logger->error(
                'The notification mail about the submitted job {uid} could not be sent.',
                ['uid' => $recordId, 'exception' => $exception],
            );
            return false;
        }

        return true;
    }

    /**
     * TYPO3 v14 creates mails through `TemplatedEmailFactory`, which adds the mail template
     * paths and the format a site sets in the site set `typo3/email`. TYPO3 v13 has no
     * factory and no such site settings, and gets the mail of the global configuration.
     *
     * @todo Inject the factory once TYPO3 v13 support is dropped.
     */
    private function createEmail(): FluidEmail
    {
        if (class_exists(TemplatedEmailFactory::class)) {
            return GeneralUtility::makeInstance(TemplatedEmailFactory::class)->createFromRequest($this->request);
        }

        return GeneralUtility::makeInstance(FluidEmail::class)->setRequest($this->request);
    }

    /**
     * A link to the job in the backend that a backend user can open from a mail. A link
     * with a token works only in the session it was created for, and the frontend has no
     * backend session to create one for: it gets the token `dummyToken`, which the
     * backend rejects with a redirect to the login, and a logged-in user lands on the
     * dashboard. The shareable link carries no token. The backend sends a user who opens
     * it through the login, which redirects to the record. The redirect keeps only the
     * arguments the route `record_edit` allows, so a return URL would be dropped there.
     * The link is absolute, with the host of the request the form was submitted with.
     */
    public function buildUrl(int $recordId): string
    {
        return (string)$this->backendUriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    'tx_academicjobs_domain_model_job' => [
                        $recordId => 'edit',
                    ],
                ],
            ],
            BackendUriBuilder::SHAREABLE_URL,
        );
    }

    /**
     * TYPO3 v14 reads the `_LOCAL_LANG` override of the plugin only from the Extbase request
     * it is handed; without one, only the override of the extension applies. TYPO3 v13 takes
     * both from the configuration manager and has no parameter for the request. The argument
     * list is spread so that one call fits both signatures.
     *
     * @todo Pass the request directly once TYPO3 v13 support is dropped.
     */
    private function translateAlert(
        string $alert,
        string $missing = 'Missing translation!'
    ): string {
        $parameters = ['tx_academicjobs.fe.alert.' . $alert, 'AcademicJobs', null, null];
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            $parameters[] = $this->request;
        }
        return LocalizationUtility::translate(...$parameters) ?? $missing;
    }

    /**
     * Determine current displayed page id. Works only when used in FE context.
     */
    private function determineCurrentPageId(): int
    {
        return (int)($this->request->getAttribute('frontend.page.information')?->getId() ?? 0);
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
