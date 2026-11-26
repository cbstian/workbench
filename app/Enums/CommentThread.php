<?php

namespace App\Enums;

/**
 * Conversation a comment belongs to (P-04, decidida 2026-11-26): the team's
 * internal thread, or the conversation with the client that client users see
 * in their portal. A reply always stays in the thread of its root.
 */
enum CommentThread: string
{
    case Internal = 'internal';
    case Client = 'client';

    /**
     * Spanish title of the conversation.
     */
    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Comentarios internos',
            self::Client => 'Conversación con el cliente',
        };
    }

    /**
     * Policy ability needed to read the thread.
     */
    public function viewAbility(): string
    {
        return match ($this) {
            self::Internal => 'view',
            self::Client => 'viewClientThread',
        };
    }

    /**
     * Policy ability needed to post or reply in the thread.
     */
    public function commentAbility(): string
    {
        return match ($this) {
            self::Internal => 'comment',
            self::Client => 'commentInClientThread',
        };
    }
}
