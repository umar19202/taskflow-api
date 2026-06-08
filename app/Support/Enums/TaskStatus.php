<?php

namespace App\Support\Enums;

enum TaskStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Done       = 'done';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',
            self::InProgress => 'In Progress',
            self::InReview   => 'In Review',
            self::Done       => 'Done',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Done, self::Cancelled], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
