<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description;

    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeLeft
     */
    private ?int $lft;

    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeLevel
     */
    private ?int $lvl;

    /**
     * @ORM\Column(type="integer")
     * @Gedmo\TreeRight
     */
    private ?int $rgt;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="tags")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Gedmo\TreeRoot
     */
    private ?Project $root;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class, inversedBy="children")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Gedmo\TreeParent
     */
    private ?Tag $parent;

    /**
     * @ORM\OneToMany(targetEntity=Tag::class, mappedBy="parent")
     * @ORM\OrderBy({"lft"="ASC"})
     */
    private Collection $children;

    /**
     * @ORM\OneToMany(targetEntity=Annotation::class, mappedBy="tag", orphanRemoval=true)
     */
    private Collection $annotations;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tags")
     * @ORM\JoinColumn(nullable=false)
     * @Gedmo\Blameable(on="create")
     */
    private ?User $createdBy;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->annotations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRoot(): ?Project
    {
        return $this->root;
    }

    public function setRoot(?Project $root): self
    {
        $this->root = $root;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Annotation[]
     */
    public function getAnnotations(): Collection
    {
        return $this->annotations;
    }

    public function addAnnotation(Annotation $annotation): self
    {
        if (!$this->annotations->contains($annotation)) {
            $this->annotations[] = $annotation;
            $annotation->setTag($this);
        }

        return $this;
    }

    public function removeAnnotation(Annotation $annotation): self
    {
        if ($this->annotations->removeElement($annotation)) {
            // set the owning side to null (unless already changed)
            if ($annotation->getTag() === $this) {
                $annotation->setTag(null);
            }
        }

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

    public function getAncestors(): array
    {
        $ancestors = [];
        $tag = $this;

        while ($parent = $tag->getParent()) {
            array_unshift($ancestors, $parent);
            $tag = $parent;
        }

        return $ancestors;
    }
}
