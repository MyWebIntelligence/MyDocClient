<?php

namespace App\Entity;

use App\Repository\AnnotationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=AnnotationRepository::class)
 */
class Annotation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="annotations")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Document $document;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class, inversedBy="annotations")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Tag $tag;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $content;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="annotations")
     * @ORM\JoinColumn(nullable=false)
     * @Gedmo\Blameable(on="create")
     */
    private ?User $createdBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;

        return $this;
    }
}
