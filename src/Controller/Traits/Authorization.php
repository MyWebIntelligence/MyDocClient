<?php

namespace App\Controller\Traits;

use App\Entity\Permission;
use App\Entity\Project;
use App\Entity\User;

trait Authorization
{

    protected function isProjectOwner(User $user, Project $project): bool
    {
        return $user === $project->getOwner();
    }

    protected function canEdit(User $user, Project $project): bool
    {
        return $this->isProjectOwner($user, $project)
            || $this->isGrantedProject($user, $project, Permission::ROLE_EDITOR);
    }

    protected function canRead(User $user, Project $project): bool
    {
        return $this->isProjectOwner($user, $project)
            || $this->isGrantedProject($user, $project, Permission::ROLE_EDITOR)
            || $this->isGrantedProject($user, $project, Permission::ROLE_READER);
    }

    protected function isGrantedProject(User $user, Project $project, string $role): bool
    {
        foreach ($user->getPermissions() as $permission) {
            if ($project === $permission->getProject() && $permission->getRole() === $role) {
                return true;
            }
        }

        return false;
    }

    protected function getRole(User $user, Project $project): string
    {
        if ($this->isProjectOwner($user, $project)) {
            return 'Propriétaire';
        }

        if ($this->isGrantedProject($user, $project, Permission::ROLE_EDITOR)) {
            return 'Éditeur';
        }

        if ($this->isGrantedProject($user, $project, Permission::ROLE_READER)) {
            return 'Lecteur';
        }

        return 'Aucun droit';
    }
}