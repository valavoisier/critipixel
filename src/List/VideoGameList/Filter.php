<?php

declare(strict_types=1);

namespace App\List\VideoGameList;

use App\Model\Entity\Tag;

final class Filter
{
    /**
     * @param Tag[] $tags
     */
    public function __construct(
        private ?string $search = null,
        private array $tags = [],
        // Indique si des IDs de tags ont été soumis mais ignorés par EntityType (IDs inexistants en BDD)
        private bool $hasInvalidTags = false
    ) {
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): Filter
    {
        $this->search = $search;
        return $this;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): Filter
    {
        $this->tags = $tags;
        return $this;
    }

    // Retourne true si des tags invalides (IDs inexistants) ont été soumis dans le formulaire
    public function hasInvalidTags(): bool
    {
        return $this->hasInvalidTags;
    }

    public function setHasInvalidTags(bool $hasInvalidTags): Filter
    {
        $this->hasInvalidTags = $hasInvalidTags;
        return $this;
    }
}
