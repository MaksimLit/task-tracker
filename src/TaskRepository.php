<?php

declare(strict_types=1);

final class TaskRepository
{
    private const string FILE = 'tasks.json';

    public function __construct(private readonly string $storagePath = self::FILE) {}

    public function findById(int $id): Task
    {
        $tasks = $this->all();

        foreach ($tasks as $taskData) {
            if ($taskData['id'] === $id) {
                return $this->deserialize($taskData);
            }
        }

        throw new \RuntimeException("Task with ID $id not found");
    }

    public function update(Task $task): void
    {
        $tasks = $this->all();
        $found = false;

        foreach ($tasks as &$taskData) {
            if ($taskData['id'] === $task->id) {
                $taskData = $this->serialize($task);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \RuntimeException("Task with ID {$task->id} not found for update");
        }

        file_put_contents($this->storagePath, json_encode($tasks, JSON_PRETTY_PRINT));
    }

    public function add(Task $task): void
    {
        $tasks = $this->all();
        $tasks[] = $this->serialize($task);
        file_put_contents($this->storagePath, json_encode($tasks, JSON_PRETTY_PRINT));
    }

    public function delete(int $id): void
    {
        $tasks = $this->all();
        $initialCount = count($tasks);

        $tasks = array_filter($tasks, fn($task) => $task['id'] !== $id);

        if (count($tasks) === $initialCount) {
            throw new RuntimeException("Task with ID {$id} not found");
        }

        file_put_contents($this->storagePath, json_encode(array_values($tasks), JSON_PRETTY_PRINT));
    }

    public function all(): array
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $data = json_decode(file_get_contents($this->storagePath), true);
        return is_array($data) ? $data : [];
    }

    public function changeStatus(int $id, string $status): void
    {
        $tasks = $this->all();
        $found = false;

        foreach ($tasks as &$task) {
            if ($task['id'] === $id) {
                $task['status'] = $status;
                $task['updatedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new RuntimeException("Task with ID $id not found");
        }

        file_put_contents($this->storagePath, json_encode($tasks, JSON_PRETTY_PRINT));
    }

    public function getByStatus(string $status): array
    {
        return array_filter($this->all(), fn($task) => $task['status'] === $status);
    }

    private function serialize(Task $task): array
    {
        return [
            'id' => $task->id,
            'description' => $task->description,
            'status' => $task->status,
            'createdAt' => $task->createdAt->format(DateTimeInterface::ATOM),
            'updatedAt' => $task->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }

    private function deserialize(array $data): Task
    {
        return new Task(
            $data['id'],
            $data['description'],
            $data['status'],
            new \DateTimeImmutable($data['createdAt']),
            new \DateTimeImmutable($data['updatedAt'])
        );
    }
}