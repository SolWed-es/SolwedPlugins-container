<?php

namespace FacturaScripts\Plugins\SolwedTheme\Lib;

class ResponseHeaders
{
    /** @var array */
    private $data = [];

    public function __construct()
    {
        $this->data = [
            'Content-Type' => 'text/html',
            'Strict-Transport-Security' => 'max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
        ];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $name): string
    {
        return $this->data[$name] ?? '';
    }

    public function remove(string $name): self
    {
        unset($this->data[$name]);

        return $this;
    }

    public function set(string $name, string $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }
}
