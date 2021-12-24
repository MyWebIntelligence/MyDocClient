<?php

namespace App\Service;

use App\Entity\Tag;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class TagUtil
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function indexCollection(array $tags): array
    {
        $indexed = [];

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $indexed[$tag->getId()] = $tag;
        }

        return $indexed;
    }

    public function updateTree(
        array $structure,
        array $indexedTagCollection,
        Tag $parent = null,
        int $level = 0): void
    {
        $treeRepository = $this->entityManager->getRepository(Tag::class);

        foreach ($structure as $i => $node) {
            if (array_key_exists($node->id, $indexedTagCollection)) {
                /** @var Tag $tag */
                $tag = $indexedTagCollection[$node->id];
                $tag->setParent($parent);
                $tag->setLvl($level);
                $this->entityManager->persist($tag);
                $this->updateTree($node->children, $indexedTagCollection, $tag, $level + 1);
            }
        }
    }

}