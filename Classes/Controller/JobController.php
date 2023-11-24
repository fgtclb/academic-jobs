<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Controller;

use FGTCLB\AcademicJobs\DateTime\IsoDateTime;
use FGTCLB\AcademicJobs\Domain\Model\Contact;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\ContactRepository;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use FGTCLB\AcademicJobs\Property\TypeConverter\JobAvatarImageUploadConverter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Extbase\Service\ImageService;

class JobController extends ActionController
{
    private const JOB_HIDDEN = true;

    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly ContactRepository $contactRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ImageService $imageService
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function showAction(Job $job): ResponseInterface
    {
        $title = $job->getTitle();
        $description = strip_tags($job->getDescription());
        $image = $this->getImageUri($job->getImage());

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
            $metaTags['og:image'] = $title;
            $metaTags['og:image:alt'] = $image;
            $metaTags['twitter:image'] = $image;
            $metaTags['twitter:image:alt'] = $title;
        }

        $this->setMetaTags($metaTags);

        $this->view->assign('job', $job);
        return $this->htmlResponse();
    }

    public function getImageUri($imageObject): ?string
    {
        if ($imageObject === null) {
            return null;
        }
        $originalResource = $imageObject->getOriginalResource();

        if ($originalResource === null) {
            return null;
        }

        return $this->imageService->getImageUri($originalResource, true);
    }

    private function setMetaTags(array $metaTags)
    {
        $metaTagManager = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);

        foreach ($metaTags as $property => $content) {
            $metaTagManagerForProperty = $metaTagManager->getManagerForProperty($property);
            $metaTagManagerForProperty->addProperty($property, $content);
        }
    }

    public function newJobFormAction(Job $job = null): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function listAction(): ResponseInterface
    {
        $jobs = [];
        $jobType = $this->settings['job']['type'] ?? 0;

        if ($jobType > 0) {
            $jobs = $this->jobRepository->findByJobType((int)$jobType);
        } else {
            $jobs = $this->jobRepository->findAll();
        }

        $this->view->assign('jobs', $jobs);
        $this->view->render();
        return $this->htmlResponse();
    }
    public function initializeSaveJobAction(): void
    {
        if ($this->request->hasArgument('job')) {
            $jobArgumentConfiguration = $this->arguments->getArgument('job')->getPropertyMappingConfiguration();

            $propertiesToCpnvert = [
                'employmentStartDate',
                'starttime',
                'endtime',
            ];

            foreach ($propertiesToCpnvert as $propertyToConvert) {
                $jobArgumentConfiguration->forProperty($propertyToConvert)
                    ->setTypeConverterOptions(
                        DateTimeConverter::class,
                        [
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT => IsoDateTime::FORMAT,
                        ]
                    );
            }
        }

        $targetFolderIdentifier = $this->settings['saveForm']['jobLogo']['targetFolder'] ?? null;
        $maxFilesize = $this->settings['saveForm']['jobLogo']['validation']['maxFileSize'] ?? '0kb';
        $allowedImeTypes = $this->settings['saveForm']['jobLogo']['validation']['allowedMimeTypes'] ?? '';
        $jobAvatarImageUploadConverter = GeneralUtility::makeInstance(JobAvatarImageUploadConverter::class);

        $this->arguments
            ->getArgument('job')
            ->getPropertyMappingConfiguration()
            ->forProperty('image')
            ->setTypeConverter($jobAvatarImageUploadConverter)
            ->setTypeConverterOptions(
                JobAvatarImageUploadConverter::class,
                [
                    JobAvatarImageUploadConverter::CONFIGURATION_TARGET_DIRECTORY_COMBINED_IDENTIFIER => $targetFolderIdentifier,
                    JobAvatarImageUploadConverter::CONFIGURATION_MAX_UPLOAD_SIZE => $maxFilesize,
                    JobAvatarImageUploadConverter::CONFIGURATION_ALLOWED_MIME_TYPES => $allowedImeTypes,
                ]
            );
    }

    public function saveJobAction(Job $job): void
    {

        $job->setHidden((int)self::JOB_HIDDEN);
        $this->jobRepository->add($job);
        $this->persistenceManager->persistAll();


        $afterSaveJobEvent = new AfterSaveJobEvent($job);
        $this->eventDispatcher->dispatch($afterSaveJobEvent);

        $this->sendEmail($jobs->getUid());

        $this->redirect('list');
    }

    public function sendEmail(int $recordId): void
    {
        $url = $this->buildUrl($recordId);

        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail->to($this->settings['email']['recipientEmail']);
        $mail->from($this->settings['email']['senderEmail']);
        $mail->subject($this->settings['email']['subject']);
        $mail->text('A new job has been posted. Please check the TYPO3 backend: ' . $url);
        $mail->send();
    }
    public function buildUrl(int $recordId): string
    {
        $path = $this->backendUriBuilder
        ->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    'tx_academicjobs_domain_model_job' => [
                        $recordId => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]
        );

        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $path;
    }
}
