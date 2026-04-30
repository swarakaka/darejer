<?php

declare(strict_types=1);

namespace Darejer\Tests\Fixtures;

enum BadgeFixtureStatus: string
{
    case Posted = 'posted';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Posted => __('Posted'),
            self::Draft => __('Draft'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Posted => 'success',
            self::Draft => 'neutral',
        };
    }
}
