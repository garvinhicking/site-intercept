<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Enum\DocumentationStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentationJarRepository")
 * @ORM\HasLifecycleCallbacks
 * @codeCoverageIgnore
 */
class DocumentationJar
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $repositoryUrl;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private $publicComposerJsonUrl;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $packageName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $branch;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $lastRenderedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $targetBranchDirectory;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private $typeLong;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private $typeShort;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $status;

    /**
     * @return mixed
     */
    public function getTypeShort(): ?string
    {
        return $this->typeShort;
    }

    /**
     * @param mixed $typeShort
     * @return DocumentationJar
     */
    public function setTypeShort(string $typeShort): self
    {
        $this->typeShort = $typeShort;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeLong(): ?string
    {
        return $this->typeLong;
    }

    /**
     * @param mixed $typeLong
     * @return DocumentationJar
     */
    public function setTypeLong(string $typeLong): self
    {
        $this->typeLong = $typeLong;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    /**
     * @param string $repositoryUrl
     * @return DocumentationJar
     */
    public function setRepositoryUrl(string $repositoryUrl): self
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPublicComposerJsonUrl(): ?string
    {
        return $this->publicComposerJsonUrl;
    }

    /**
     * @param string $publicComposerJsonUrl
     * @return DocumentationJar
     */
    public function setPublicComposerJsonUrl(string $publicComposerJsonUrl): self
    {
        $this->publicComposerJsonUrl = $publicComposerJsonUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @param mixed $vendor
     * @return DocumentationJar
     */
    public function setVendor(string $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return DocumentationJar
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     * @return DocumentationJar
     */
    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return DocumentationJar
     */
    public function setBranch(string $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastRenderedAt(): ?\DateTimeInterface
    {
        return $this->lastRenderedAt;
    }

    public function setLastRenderedAt(\DateTimeInterface $lastRenderedAt): self
    {
        $this->lastRenderedAt = $lastRenderedAt;

        return $this;
    }

    public function getTargetBranchDirectory(): ?string
    {
        return $this->targetBranchDirectory;
    }

    public function setTargetBranchDirectory(string $targetBranchDirectory): self
    {
        $this->targetBranchDirectory = $targetBranchDirectory;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActionable(): bool
    {
        return $this->status === DocumentationStatus::STATUS_RENDERED;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        // Set created at if record is first persisted
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
        // Update last rendered each time record is updated
        $this->setLastRenderedAt(new \DateTime('now'));
    }
}
