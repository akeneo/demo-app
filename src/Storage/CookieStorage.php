<?php

declare(strict_types=1);

namespace App\Storage;

class CookieStorage
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function get(string $name): mixed
    {
        return \array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }

    public function set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateWith(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
