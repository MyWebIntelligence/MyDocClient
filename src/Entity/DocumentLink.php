<?php

namespace App\Entity;

use App\Repository\DocumentLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocumentLinkRepository::class)
 */
class DocumentLink
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="sourceOf")
     * @ORM\JoinColumn(nullable=false)
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="targetOf")
     * @ORM\JoinColumn(nullable=false)
     */
    private $target;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?Document
    {
        return $this->source;
    }

    public function setSource(?Document $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getTarget(): ?Document
    {
        return $this->target;
    }

    public function setTarget(?Document $target): self
    {
        $this->target = $target;

        return $this;
    }

}
