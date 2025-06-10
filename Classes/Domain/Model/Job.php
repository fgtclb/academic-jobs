<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @todo Replace annotation with attributes when TYPO3 v11 support is dropped.
 * @see  https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96688-AttributesForExtbaseAnnotations.html#feature-96688-attributes-for-extbase-annotations
 */
class Job extends AbstractEntity
{
    protected string $title = '';
    protected ?\DateTime $employmentStartDate = null;
    protected string $description = '';
    /**
     * @Cascade("remove")
     */
    protected ?FileReference $image = null;
    protected string $companyName = '';
    protected string $sector = '';
    protected string $requiredDegree = '';
    protected string $contractualRelationship = '';
    protected int $alumniRecommend = 0;
    protected int $internationalsWelcome = 0;
    protected int $employmentType = 0;
    protected string $workLocation = '';
    protected string $link = '';
    protected string $slug = '';
    protected int $type = 0;
    protected int $hidden = 0;
    protected ?\DateTime $starttime = null;
    protected ?\DateTime $endtime = null;
    protected string $contactName = '';
    protected string $contactEmail = '';
    protected string $contactPhone = '';
    protected string $contactAdditionalInformation = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setEmploymentStartDate(?\DateTime $employmentStartDate = null): void
    {
        $this->employmentStartDate = $employmentStartDate;
    }

    public function getEmploymentStartDate(): ?\DateTime
    {
        return $this->employmentStartDate;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setSector(string $sector): void
    {
        $this->sector = $sector;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setRequiredDegree(string $requiredDegree): void
    {
        $this->requiredDegree = $requiredDegree;
    }

    public function getRequiredDegree(): string
    {
        return $this->requiredDegree;
    }

    public function setContractualRelationship(string $contractualRelationship): void
    {
        $this->contractualRelationship = $contractualRelationship;
    }

    public function getContractualRelationship(): string
    {
        return $this->contractualRelationship;
    }

    public function setAlumniRecommend(int $alumniRecommend): void
    {
        $this->alumniRecommend = $alumniRecommend;
    }

    public function getAlumniRecommend(): int
    {
        return $this->alumniRecommend;
    }

    public function setInternationalsWelcome(int $internationalsWelcome): void
    {
        $this->internationalsWelcome = $internationalsWelcome;
    }

    public function getInternationalsWelcome(): int
    {
        return $this->internationalsWelcome;
    }

    public function setWorkLocation(string $workLocation): void
    {
        $this->workLocation = $workLocation;
    }

    public function getWorkLocation(): string
    {
        return $this->workLocation;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setEmploymentType(int $employmentType): void
    {
        $this->employmentType = $employmentType;
    }

    public function getEmploymentType(): int
    {
        return $this->employmentType;
    }

    public function setStarttime(?\DateTime $starttime = null): void
    {
        $this->starttime = $starttime;
    }

    public function getStarttime(): ?\DateTime
    {
        return $this->starttime;
    }

    public function setEndtime(?\DateTime $endtime = null): void
    {
        $this->endtime = $endtime;
    }

    public function getEndtime(): ?\DateTime
    {
        return $this->endtime;
    }

    public function setHidden(int $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getHidden(): int
    {
        return $this->hidden;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setContactName(string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactPhone(string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone;
    }

    public function setContactAdditionalInformation(string $contactAdditionalInformation): void
    {
        $this->contactAdditionalInformation = $contactAdditionalInformation;
    }

    public function getContactAdditionalInformation(): string
    {
        return $this->contactAdditionalInformation;
    }
}
