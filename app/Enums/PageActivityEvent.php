<?php

namespace App\Enums;

/**
 * Events recorded in the history of a wiki page (RN-09). Pages have no versions.
 */
enum PageActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case BodyUpdated = 'body_updated';
    case Moved = 'moved';
    case LabelAdded = 'label_added';
    case LabelRemoved = 'label_removed';
    case FileAdded = 'file_added';
    case FileDeleted = 'file_deleted';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
