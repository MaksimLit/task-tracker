<?php

declare(strict_types=1);

final readonly class CliApplication
{
    public function __construct(private TaskRepository $repository) {}

    public function run(array $argv): void
    {
        match ($argv[1] ?? null) {
            'add' => $this->addTask($argv),
            'update' => $this->updateTask($argv),
            'delete' => $this->deleteTask($argv),
            'list' => $this->listTasks($argv[2] ?? null),
            'mark-in-progress' => $this->markInProgress($argv),
            'mark-done' => $this->markDone($argv),
            default => $this->showHelp(),
        };
    }

    private function listTasks(?string $status = null): void
    {
        $tasks = $status
            ? $this->repository->getByStatus($status)
            : $this->repository->all();

        if (empty($tasks)) {
            echo "No tasks found" . ($status ? " with status '$status'" : "") . "\n";
            return;
        }

        foreach ($tasks as $task) {
            $created = (new DateTime($task['createdAt']))->format('Y-m-d H:i');
            $updated = (new DateTime($task['updatedAt']))->format('Y-m-d H:i');
            echo sprintf(
                "%d. [%-10s] %s (created: %s) (updated: %s)\n",
                $task['id'],
                strtoupper($task['status']),
                $task['description'],
                $created,
                $updated
            );
        }
    }

    private function addTask(array $argv): void
    {
        if (!isset($argv[2])) {
            throw new InvalidArgumentException('Description is required');
        }

        $tasks = $this->repository->all();
        $newId = count($tasks) + 1;

        $task = new Task(
            $newId,
            $argv[2],
            Task::STATUS_TODO,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $this->repository->add($task);
        echo "Task added successfully (ID: $newId)\n";
    }

    private function updateTask(array $argv): void
    {
        $this->validateId($argv);

        if (!isset($argv[3])) {
            throw new \InvalidArgumentException('New description is required');
        }

        $task = $this->repository->findById((int)$argv[2]);
        $updatedTask = new Task(
            $task->id,
            $argv[3],
            $task->status,
            $task->createdAt,
            new \DateTimeImmutable()
        );

        $this->repository->update($updatedTask);
    }

    private function markInProgress(array $argv): void
    {
        $this->validateId($argv);
        $id = (int)$argv[2];
        $this->repository->changeStatus($id, Task::STATUS_IN_PROGRESS);
        echo "Task #$id marked as in progress\n";
    }

    private function markDone(array $argv): void
    {
        $this->validateId($argv);
        $id = (int)$argv[2];
        $this->repository->changeStatus($id, Task::STATUS_DONE);
        echo "Task #$id marked as done\n";
    }

    private function deleteTask(array $argv): void
    {
        if (!isset($argv[2])) {
            throw new InvalidArgumentException('ID is required');
        }

        if (!is_numeric($argv[2])) {
            throw new InvalidArgumentException('ID must be a number');
        }

        $id = (int)$argv[2];

        try {
            $this->repository->delete($id);
        } catch (RuntimeException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function validateId(array $argv): void
    {
        if (!isset($argv[2])) {
            throw new InvalidArgumentException('ID is required');
        }
        if (!is_numeric($argv[2])) {
            throw new InvalidArgumentException('ID must be a number');
        }
    }

    private function showHelp(): void
    {
        echo "Usage:\n";
        echo "  task-cli add \"<description>\"\n";
        echo "  task-cli update <id> \"<new_description>\"\n";
        echo "  task-cli delete <id>\"\n";
        echo "  task-cli mark-in-progress <id>\"\n";
        echo "  task-cli mark-done <id>\"\n";
        echo "  task-cli list <id>\"\n";
    }
}
