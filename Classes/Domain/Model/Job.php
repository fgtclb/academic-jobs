<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Job extends AbstractEntity
{
    /**
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected string $title;

    /**
     * @var \DateTime
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $employmentStartDate;

    /**
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected string $description;

    /**
     * @Cascade("remove")
     */
    protected ?FileReference $image = null;

    /**
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected string $companyName;

    protected string $sector;

    /**
     * employmentType
     *
     * @var int
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected int $employmentType;

    protected string $workLocation;

    protected string $link;

    protected string $slug = '';

    protected int $type;

    protected int $hidden = 0;

    /**
     * @var Contact
     */
    protected Contact $contact;

    /**
     * @var \DateTime
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $starttime;

    /**
     * @var \DateTime
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $endtime;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the employmentStartDate
     *
     * @return \DateTime
     */
    public function getEmploymentStartDate()
    {
        return $this->employmentStartDate;
    }

    /**
     * Sets the employmentStartDate
     *
     * @param \DateTime $employmentStartDate
     */
    public function setEmploymentStartDate(\DateTime $employmentStartDate): void
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

    public function getContact(): ?Contact {
        return $this->contact;
    }

    public function setContact(Contact $contact): void {
        $this->contact = $contact;
    }

    /**
     * employmentType
     *
     * @return int
     */
    public function getEmploymentType(): int
    {
        return $this->employmentType;
    }

    /**
     * employmentType
     *
     * @param int $employmentType employmentType
     * @return self
     */
    public function setEmploymentType(int $employmentType): self
    {
        $this->employmentType = $employmentType;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStarttime()
    {
        return $this->starttime;
    }

    /**
     * @param \DateTime $starttime
     */
    public function setStarttime($starttime): void
    {
        $this->starttime = $starttime;
    }

    /**
     * @return \DateTime
     */
    public function getEndtime()
    {
        return $this->endtime;
    }

    /**
     * @param \DateTime $endtime
     */
    public function setEndtime($endtime): void
    {
        $this->endtime = $endtime;
    }

    public function getHidden(): int
    {
        return $this->hidden;
    }

    public function setHidden(int $hidden): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): self
    {
        $this->image = $image;
        return $this;
    }
}
