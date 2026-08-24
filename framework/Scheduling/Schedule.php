<?php
declare(strict_types=1);

namespace Nexus\Scheduling;

class Schedule
{
    protected array $events = [];

    public function call(callable $callback): Event
    {
        $event = new Event($callback);
        $this->events[] = $event;
        return $event;
    }

    public function exec(string $command): Event
    {
        return $this->call(function () use ($command) {
            exec($command);
        });
    }

    /**
     * @return Event[]
     */
    public function getDueEvents(): array
    {
        return array_filter($this->events, fn(Event $event) => $event->isDue());
    }

    /**
     * @return Event[]
     */
    public function events(): array
    {
        return $this->events;
    }
}

class Event
{
    protected mixed $callback;
    protected string $expression = '* * * * *';

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function everyMinute(): static
    {
        $this->expression = '* * * * *';
        return $this;
    }

    public function hourly(): static
    {
        $this->expression = '0 * * * *';
        return $this;
    }

    public function daily(): static
    {
        $this->expression = '0 0 * * *';
        return $this;
    }

    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    public function isDue(): bool
    {
        $parts = explode(' ', $this->expression);
        if (count($parts) !== 5) {
            return false;
        }

        $minute = date('i');
        $hour = date('H');
        $day = date('d');
        $month = date('m');
        $dayOfWeek = date('w');

        return $this->matchCronPart($parts[0], (int) $minute) &&
               $this->matchCronPart($parts[1], (int) $hour) &&
               $this->matchCronPart($parts[2], (int) $day) &&
               $this->matchCronPart($parts[3], (int) $month) &&
               $this->matchCronPart($parts[4], (int) $dayOfWeek);
    }

    protected function matchCronPart(string $part, int $current): bool
    {
        if ($part === '*') {
            return true;
        }

        if (str_contains($part, '/')) {
            [$step, $interval] = explode('/', $part);
            return $interval > 0 && ($current % (int) $interval) === 0;
        }

        return (int) $part === $current;
    }

    public function run(): mixed
    {
        return call_user_func($this->callback);
    }
}
