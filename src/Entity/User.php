<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Un compte existe déjà avec cette adresse e-mail")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private ?string $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $organization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $title;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string")
     */
    private ?string $password;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isVerified = false;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="owner", orphanRemoval=true)
     */
    private Collection $projects;

    /**
     * @ORM\OneToMany(targetEntity=Permission::class, mappedBy="user", orphanRemoval=true)
     */
    private Collection $permissions;

    /**
     * @ORM\OneToMany(targetEntity=Annotation::class, mappedBy="createdBy", orphanRemoval=true)
     */
    private Collection $annotations;

    /**
     * @ORM\OneToMany(targetEntity=Tag::class, mappedBy="createdBy", orphanRemoval=true)
     */
    private Collection $tags;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->annotations = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setOwner($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getOwner() === $this) {
                $project->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Permission[]
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
            $permission->setUser($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getUser() === $this) {
                $permission->setUser(null);
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
            $annotation->setCreatedBy($this);
        }

        return $this;
    }

    public function removeAnnotation(Annotation $annotation): self
    {
        if ($this->annotations->removeElement($annotation)) {
            // set the owning side to null (unless already changed)
            if ($annotation->getCreatedBy() === $this) {
                $annotation->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Annotation[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->setCreatedBy($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getCreatedBy() === $this) {
                $tag->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function isProjectOwner(Project $project): bool
    {
        return $this === $project->getOwner();
    }

    public function canEditProject(Project $project): bool
    {
        return $this->isProjectOwner($project)
            || $this->isGrantedProject($project, Permission::ROLE_EDITOR);
    }

    public function canReadProject(Project $project): bool
    {
        return $this->isProjectOwner($project)
            || $this->isGrantedProject($project, Permission::ROLE_EDITOR)
            || $this->isGrantedProject($project, Permission::ROLE_READER);
    }

    public function isGrantedProject(Project $project, string $role): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if ($project === $permission->getProject() && $permission->getRole() === $role) {
                return true;
            }
        }

        return false;
    }

    public function getProjectRole(Project $project): string
    {
        if ($this->isProjectOwner($project)) {
            return 'Propriétaire';
        }

        if ($this->isGrantedProject($project, Permission::ROLE_EDITOR)) {
            return 'Éditeur';
        }

        if ($this->isGrantedProject($project, Permission::ROLE_READER)) {
            return 'Lecteur';
        }

        return 'Aucun droit';
    }
}
