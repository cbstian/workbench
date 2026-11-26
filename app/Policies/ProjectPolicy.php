<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ClientPortal;

/**
 * The super admin creates projects and manages their teams (RN-13, RN-26);
 * admins and members only reach the projects they are assigned to (RN-21),
 * where their workspace role's permissions apply.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->ownsWorkspace($project->workspace) || $user->isOnTeamOf($project);
    }

    /**
     * See the project in the client portal (RN-05): a project of one of the
     * user's clients marked visible to the client.
     */
    public function viewInPortal(User $user, Project $project): bool
    {
        return app(ClientPortal::class)->canSeeProject($user, $project);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->ownsOrIsTeamMemberWith($user, $project, Permission::ProjectsUpdate);
    }

    /**
     * Statuses, priorities, labels and initial values (F-05).
     */
    public function configure(User $user, Project $project): bool
    {
        return $this->ownsOrIsTeamMemberWith($user, $project, Permission::ProjectsConfigure);
    }

    /**
     * Add existing users to the team or remove them (RN-26).
     */
    public function manageTeam(User $user, Project $project): bool
    {
        return $user->ownsWorkspace($project->workspace);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->ownsWorkspace($project->workspace);
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->ownsWorkspace($project->workspace);
    }

    private function ownsOrIsTeamMemberWith(User $user, Project $project, Permission $permission): bool
    {
        if ($user->ownsWorkspace($project->workspace)) {
            return true;
        }

        return $user->isOnTeamOf($project) && $user->hasWorkspacePermission($project->workspace_id, $permission);
    }
}
