<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Contact extends AbstractEntity
{
    protected string $name;

    protected string $email;

    protected string $phone;

    protected string $additionalInformation;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getAdditionalInformation(): string
    {
        return $this->additionalInformation;
    }

    /**
     * @param string $additionalInformation
     */
    public function setAdditionalInformation(string $additionalInformation): void
    {
        $this->additionalInformation = $additionalInformation;
    }
}
