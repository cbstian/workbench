<?php

namespace App\Enums;

/**
 * Events recorded in the history of a task (RN-08).
 */
enum TaskActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case AssigneeAdded = 'assignee_added';
    case AssigneeRemoved = 'assignee_removed';
    case LabelAdded = 'label_added';
    case LabelRemoved = 'label_removed';
    case SubtaskAdded = 'subtask_added';
    case SubtaskRemoved = 'subtask_removed';
    case Moved = 'moved';
    case CommentAdded = 'comment_added';
    case CommentDeleted = 'comment_deleted';
    case AttachmentAdded = 'attachment_added';
    case AttachmentDeleted = 'attachment_deleted';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
