<?php
namespace App\Core;

/**
 * Server-side input checks. Example:
 *   $v = new Validator($_POST);
 *   $v->required('email')->email('email');
 *   if ($v->fails()) ...
 */
class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function get(string $field): string
    {
        $v = $this->data[$field] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    private function fail(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function required(string $field, string $label = null): self
    {
        if ($this->get($field) === '') {
            $this->fail($field, ($label ?? ucfirst(str_replace('_', ' ', $field))) . ' is required.');
        }
        return $this;
    }

    public function max(string $field, int $len): self
    {
        if (mb_strlen($this->get($field)) > $len) {
            $this->fail($field, "Must be at most $len characters.");
        }
        return $this;
    }

    public function email(string $field): self
    {
        $v = $this->get($field);
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, 'Enter a valid email address.');
        }
        return $this;
    }

    public function phone(string $field): self
    {
        $v = $this->get($field);
        if ($v !== '' && !preg_match('/^\+?[0-9 \-]{7,20}$/', $v)) {
            $this->fail($field, 'Enter a valid phone number (digits, spaces, + or -).');
        }
        return $this;
    }

    /** At least 8 characters with upper case, lower case, a number and a symbol. */
    public function strongPassword(string $field): self
    {
        $v = $this->data[$field] ?? '';
        if (!is_string($v) || strlen($v) < 8 || !preg_match('/[A-Z]/', $v) || !preg_match('/[a-z]/', $v)
            || !preg_match('/[0-9]/', $v) || !preg_match('/[^A-Za-z0-9]/', $v)) {
            $this->fail($field, 'Password needs 8+ characters with an uppercase letter, a lowercase letter, a number and a symbol.');
        }
        return $this;
    }

    public function same(string $field, string $other, string $message): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$other] ?? null)) {
            $this->fail($field, $message);
        }
        return $this;
    }

    public function integer(string $field, int $min = null, int $max = null): self
    {
        $v = $this->get($field);
        if ($v === '') {
            return $this;
        }
        if (filter_var($v, FILTER_VALIDATE_INT) === false
            || ($min !== null && (int) $v < $min) || ($max !== null && (int) $v > $max)) {
            $range = $min !== null && $max !== null ? " between $min and $max" : ($min !== null ? " of at least $min" : '');
            $this->fail($field, "Enter a whole number$range.");
        }
        return $this;
    }

    public function number(string $field, float $min = null, float $max = null): self
    {
        $v = $this->get($field);
        if ($v === '') {
            return $this;
        }
        if (!is_numeric($v) || ($min !== null && (float) $v < $min) || ($max !== null && (float) $v > $max)) {
            $this->fail($field, 'Enter a valid number' . ($min !== null && $max !== null ? " between $min and $max" : '') . '.');
        }
        return $this;
    }

    public function date(string $field): self
    {
        $v = $this->get($field);
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if ($v !== '' && (!$d || $d->format('Y-m-d') !== $v)) {
            $this->fail($field, 'Enter a valid date.');
        }
        return $this;
    }

    public function time(string $field): self
    {
        $v = $this->get($field);
        if ($v !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $v)) {
            $this->fail($field, 'Enter a valid time.');
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        if (!in_array($this->get($field), $allowed, true)) {
            $this->fail($field, 'Choose a valid option.');
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        return $this->fail($field, $message);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
