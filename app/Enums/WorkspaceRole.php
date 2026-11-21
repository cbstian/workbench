<?php

namespace App\Enums;

/**
 * Role of a user inside a workspace (docs/flujo-canonico.md, RN-15).
 *
 * The super admin is the workspace owner, not a role on this enum.
 */
enum WorkspaceRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Client = 'client';
}
