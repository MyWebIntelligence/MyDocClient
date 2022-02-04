<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"title", "description", "content"}, flags={"fulltext"})})
 */
class Document
{

    public const META_MATCH_PATTERN = '/^---\s.*?---/s';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $creator = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $contributor = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $coverage = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $date = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $subject = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $type = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $format = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $identifier = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $language = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $publisher = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $relation = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $rights = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $source = null;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="documents")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Project $project;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     * @Gedmo\Blameable(on="create")
     */
    private ?User $owner;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $content = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?DateTimeImmutable $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=Annotation::class, mappedBy="document", orphanRemoval=true)
     */
    private $annotations;

    /**
     * @ORM\OneToMany(targetEntity=DocumentLink::class, mappedBy="source", orphanRemoval=true)
     */
    private $sourceOf;

    /**
     * @ORM\OneToMany(targetEntity=DocumentLink::class, mappedBy="target", orphanRemoval=true)
     */
    private $targetOf;

    private array $metas = [
        'Title' => 'Titre',
        'Creator' => 'Auteur',
        'Contributor' => 'Contributeur',
        'Coverage' => 'Couverture',
        'Date' => 'Date',
        'Description' => 'Description',
        'Subject' => 'Sujet',
        'Type' => 'Type',
        'Format' => 'Format',
        'Identifier' => 'Identifiant',
        'Language' => 'Langue',
        'Publisher' => 'Éditeur',
        'Relation' => 'Associé',
        'Rights' => 'Droits',
        'Source' => 'Source',
    ];

    public function __construct()
    {
        $this->setCreatedAt(new DateTimeImmutable());
        $this->annotations = new ArrayCollection();
        $this->links = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(?string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getContributor(): ?string
    {
        return $this->contributor;
    }

    public function setContributor(?string $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getCoverage(): ?string
    {
        return $this->coverage;
    }

    public function setCoverage(?string $coverage): self
    {
        $this->coverage = $coverage;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
    {
        $this->date = $date;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(?string $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    public function setRelation(?string $relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    public function getRights(): ?string
    {
        return $this->rights;
    }

    public function setRights(?string $rights): self
    {
        $this->rights = $rights;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getContent($noMetadata = false): ?string
    {
        if ($noMetadata === true) {
            return preg_replace(self::META_MATCH_PATTERN, '', $this->content);
        }

        return $this->content;
    }

    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $user): self
    {
        $this->owner = $user;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMetadataDict(): array
    {
        return $this->metas;
    }

    /**
     * Update metadata mapped properties from metas parsed in content
     * @throws ParseException
     */
    public function updateMetadata(): void
    {
        preg_match(self::META_MATCH_PATTERN, $this->getContent(), $metaContent);
        $metas = Yaml::parse(trim(current($metaContent), '-'));

        if (!empty($metaContent) && is_array($metas)) {
            foreach ($metas as $property => $value) {
                if (method_exists($this, 'set' . $property)) {
                    $this->{'set' . $property}($value);
                }
            }
        }
    }

    public function formatMetadata(): string
    {
        $content = ['---'];

        foreach ($this->getMetadataDict() as $meta => $label) {
            $content[] = sprintf(
                '%s: "%s"',
                $meta,
                $this->{'get' . $meta}()
            );
        }

        $content[] = '---';

        return implode(PHP_EOL, $content);
    }

    public function getWords(): array
    {
        $text = preg_replace(['/[[:punct:]]/'],' ', $this->getContent(true));
        preg_match_all('/[\S]+/', $text, $words);

        $words = array_map(static function($value) {
            return mb_strtolower($value);
        }, current($words));

        $stopWords = [
            'à', 'a', 'au',
            'c', 'ce', 'ça', 'ces',
            'd', 'de', 'du', 'des', 'dans',
            'en', 'et', 'es', 'est', 'elle', 'elles',
            'il', 'ils',
            'j', 'je',
            'l', 'le', 'la', 'les', 'leur', 'leurs',
            'm', 'mais',
            'n', 'ne', 'nous', 'notre',
            'pas', 'pour',
            'qu', 'que', 'qui', 'quoi',
            's', 'se', 'sa', 'ses', 'sont',
            't', 'te', 'ta', 'tu', 'tes',
            'un', 'une',
            'vous', 'votre',
        ];

        return array_filter($words, static function($word) use ($stopWords) {
            return !empty($word) && !in_array($word, $stopWords, true);
        });
    }

    public function getExternalLinks(): array
    {
        $re = '/(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)(?:\([-A-Z0-9+&@#\/%=~_|$?!:,.]*\)|[-A-Z0-9+&@#\/%=~_|$?!:,.])*(?:\([-A-Z0-9+&@#\/%=~_|$?!:,.]*\)|[A-Z0-9+&@#\/%=~_|$])/im';
        preg_match_all($re, $this->getContent(true), $matches);

        return $matches[0] ?? [];
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
            $annotation->setDocument($this);
        }

        return $this;
    }

    public function removeAnnotation(Annotation $annotation): self
    {
        if ($this->annotations->removeElement($annotation)) {
            // set the owning side to null (unless already changed)
            if ($annotation->getDocument() === $this) {
                $annotation->setDocument(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DocumentLink[]
     */
    public function getSourceOf(): Collection
    {
        return $this->sourceOf;
    }

    public function addSourceOf(DocumentLink $link): self
    {
        if (!$this->sourceOf->contains($link)) {
            $this->sourceOf[] = $link;
            $link->setSource($this);
        }

        return $this;
    }

    public function removeSource(DocumentLink $link): self
    {
        if ($this->sourceOf->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getSource() === $this) {
                $link->setSource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DocumentLink[]
     */
    public function getTargetOf(): Collection
    {
        return $this->targetOf;
    }

    public function addTargetOf(DocumentLink $link): self
    {
        if (!$this->targetOf->contains($link)) {
            $this->targetOf[] = $link;
            $link->setTarget($this);
        }

        return $this;
    }

    public function removeTarget(DocumentLink $link): self
    {
        if ($this->targetOf->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getTarget() === $this) {
                $link->setTarget(null);
            }
        }

        return $this;
    }

}
