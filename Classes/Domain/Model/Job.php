<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @todo Replace annotation with attributes when TYPO3 v11 support is dropped.
 * @see  https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96688-AttributesForExtbaseAnnotations.html#feature-96688-attributes-for-extbase-annotations
 */
class Job extends AbstractEntity
{
    /**
     * @Validate("NotEmpty")
     */
    protected string $title = '';
    /**
     * @Validate("NotEmpty")
     */
    protected ?\DateTime $employmentStartDate = null;
    protected string $description = '';
    /**
     * @Cascade("remove")
     */
    protected ?FileReference $image = null;
    /**
     * @Validate("NotEmpty")
     */
    protected string $companyName = '';
    protected string $sector = '';
    protected string $requiredDegree = '';
    protected string $contractualRelationship = '';
    protected int $alumniRecommend = 0;
    protected int $internationalsWelcome = 0;
    /**
     * @Validate("NotEmpty")
     */
    protected int $employmentType = 0;
    protected string $workLocation = '';
    protected string $link = '';
    protected string $slug = '';
    protected int $type = 0;
    protected int $hidden = 0;
    protected ?Contact $contact = null;
    /**
     * @Validate("NotEmpty")
     */
    protected ?\DateTime $starttime = null;
    /**
     * @Validate("NotEmpty")
     */
    protected ?\DateTime $endtime = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getEmploymentStartDate(): ?\DateTime
    {
        return $this->employmentStartDate;
    }

    public function setEmploymentStartDate(?\DateTime $employmentStartDate = null): void
    {
        $this->employmentStartDate = $employmentStartDate;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setSector(string $sector): void
    {
        $this->sector = $sector;
    }

    public function getRequiredDegree(): string
    {
        return $this->requiredDegree;
    }

    public function setRequiredDegree(string $requiredDegree): void
    {
        $this->requiredDegree = $requiredDegree;
    }

    public function getContractualRelationship(): string
    {
        return $this->contractualRelationship;
    }

    public function setContractualRelationship(string $contractualRelationship): void
    {
        $this->contractualRelationship = $contractualRelationship;
    }

    public function getAlumniRecommend(): int
    {
        return $this->alumniRecommend;
    }

    public function setAlumniRecommend(int $alumniRecommend): void
    {
        $this->alumniRecommend = $alumniRecommend;
    }

    public function getInternationalsWelcome(): int
    {
        return $this->internationalsWelcome;
    }

    public function setInternationalsWelcome(int $internationalsWelcome): void
    {
        $this->internationalsWelcome = $internationalsWelcome;
    }

    public function getWorkLocation(): string
    {
        return $this->workLocation;
    }

    public function setWorkLocation(string $workLocation): void
    {
        $this->workLocation = $workLocation;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function getEmploymentType(): int
    {
        return $this->employmentType;
    }

    public function setEmploymentType(int $employmentType): void
    {
        $this->employmentType = $employmentType;
    }

    public function getStarttime(): ?\DateTime
    {
        return $this->starttime;
    }

    public function setStarttime(?\DateTime $starttime = null): void
    {
        $this->starttime = $starttime;
    }

    public function getEndtime(): ?\DateTime
    {
        return $this->endtime;
    }

    public function setEndtime(?\DateTime $endtime = null): void
    {
        $this->endtime = $endtime;
    }

    public function getHidden(): int
    {
        return $this->hidden;
    }

    public function setHidden(int $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }
}
