<?php

namespace App\Service;

use App\Entity\Annotation;
use App\Entity\Tag;
use App\Entity\User;

class AnnotationService
{
    public function getTagIndexed($annotations): array
    {
        $indexedAnnotations = [];

        foreach ($annotations as $annotation) {
            /** @var Tag $tag */
            if ($tag = $annotation->getTag()) {
                $index = [$tag->getId()];

                foreach ($tag->getAncestors() as $ancestor) {
                    $index[] = $ancestor->getId();
                }

                $index = implode('_', $index);

                if (!array_key_exists($index, $indexedAnnotations)) {
                    $indexedAnnotations[$index] = [];
                }

                $indexedAnnotations[$index][] = $annotation;
            }
        }

        return $indexedAnnotations;
    }

    public function getAuthors($annotations): array
    {
        $authors = [];

        /** @var Annotation $annotation */
        foreach ($annotations as $annotation) {
            /** @var User $author */
            $author = $annotation->getCreatedBy();
            if ($author && !array_key_exists($author->getId(), $authors)) {
                $authors[$author->getId()] = $author;
            }
        }

        return $authors;
    }

}