<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        $v->validate($rules);
        return $v;
    }

    private function validate(array $rules): void
    {
        foreach ($rules as $field => $ruleSet) {
            $rules_list = explode('|', $ruleSet);
            $value      = trim($this->data[$field] ?? '');

            foreach ($rules_list as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                match($name) {
                    'required' => $this->applyRequired($field, $value),
                    'min'      => $this->applyMin($field, $value, (int) $param),
                    'max'      => $this->applyMax($field, $value, (int) $param),
                    'email'    => $this->applyEmail($field, $value),
                    'match'    => $this->applyMatch($field, $value, $param),
                    'password' => $this->applyPassword($field, $value),
                    'in'       => $this->applyIn($field, $value, explode(',', $param ?? '')),
                    default    => null,
                };
            }
        }
    }

    private function applyRequired(string $field, string $value): void
    {
        if ($value === '') {
            $this->errors[$field][] = ucfirst($field) . ' is required.';
        }
    }

    private function applyMin(string $field, string $value, int $min): void
    {
        if (strlen($value) < $min) {
            $this->errors[$field][] = ucfirst($field) . " must be at least {$min} characters.";
        }
    }

    private function applyMax(string $field, string $value, int $max): void
    {
        if (strlen($value) > $max) {
            $this->errors[$field][] = ucfirst($field) . " must not exceed {$max} characters.";
        }
    }

    private function applyEmail(string $field, string $value): void
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Please enter a valid email address.';
        }
    }

    private function applyMatch(string $field, string $value, string $otherField): void
    {
        $other = trim($this->data[$otherField] ?? '');
        if ($value !== $other) {
            $this->errors[$field][] = ucfirst($field) . ' does not match ' . $otherField . '.';
        }
    }

    private function applyPassword(string $field, string $value): void
    {
        if ($value === '') return;
        if (!preg_match('/[A-Z]/', $value)) {
            $this->errors[$field][] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $value)) {
            $this->errors[$field][] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $value)) {
            $this->errors[$field][] = 'Password must contain at least one number.';
        }
    }

    private function applyIn(string $field, string $value, array $allowed): void
    {
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = ucfirst($field) . ' contains an invalid value.';
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        $first = array_values($this->errors)[0] ?? [];
        return $first[0] ?? '';
    }

    public function allErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $msg) {
                $flat[] = $msg;
            }
        }
        return $flat;
    }
}
