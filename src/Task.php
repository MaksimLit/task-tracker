<?php

declare(strict_types=1);

final readonly class Task
{
    public const string STATUS_TODO = 'todo';
    public const string STATUS_IN_PROGRESS = 'in-progress';
    public const string STATUS_DONE = 'done';

    public function __construct(
        public int               $id,
        public string            $description,
        public string            $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}