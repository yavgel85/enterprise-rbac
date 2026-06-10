<?php

declare(strict_types=1);

namespace App\Authorization;

/**
 * Evaluates a small declarative JSON condition DSL against an attribute context.
 *
 * Grammar (recursive):
 *   node      := group | leaf
 *   group     := {"all": [node, ...]} | {"any": [node, ...]} | {"not": node}
 *   leaf      := {"attr": "<path>", "op": "<operator>", "value": <literal|ref>}
 *
 * - "<path>" is a dot path resolved from the context, e.g. "deal.status".
 * - <ref> is a string starting with "$" resolved from the context too, e.g.
 *   "$user.id" — this lets you compare two attributes (owner vs current user).
 * - Supported operators: =, !=, >, <, >=, <=, in, not_in, contains.
 */
final class ConditionEvaluator
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $conditions
     */
    public function satisfies(array $context, array $conditions): bool
    {
        if ($conditions === []) {
            return true;
        }

        return $this->evaluate($context, $conditions);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $node
     */
    private function evaluate(array $context, array $node): bool
    {
        if (array_key_exists('all', $node)) {
            foreach ((array) $node['all'] as $child) {
                if (! $this->evaluate($context, (array) $child)) {
                    return false;
                }
            }

            return true;
        }

        if (array_key_exists('any', $node)) {
            foreach ((array) $node['any'] as $child) {
                if ($this->evaluate($context, (array) $child)) {
                    return true;
                }
            }

            return false;
        }

        if (array_key_exists('not', $node)) {
            return ! $this->evaluate($context, (array) $node['not']);
        }

        return $this->evaluateLeaf($context, $node);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $leaf
     */
    private function evaluateLeaf(array $context, array $leaf): bool
    {
        $attr = $leaf['attr'] ?? null;
        $op = $leaf['op'] ?? '=';

        if (! is_string($attr)) {
            return false;
        }

        $actual = $this->resolvePath($context, $attr);
        $expected = $this->resolveValue($context, $leaf['value'] ?? null);

        return $this->compare($actual, $op, $expected);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveValue(array $context, mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, '$')) {
            return $this->resolvePath($context, substr($value, 1));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolvePath(array $context, string $path): mixed
    {
        $current = $context;

        foreach (explode('.', $path) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];

                continue;
            }

            return null;
        }

        return $current;
    }

    private function compare(mixed $actual, mixed $op, mixed $expected): bool
    {
        return match ($op) {
            '=', '==' => $this->scalar($actual) === $this->scalar($expected),
            '!=', '<>' => $this->scalar($actual) !== $this->scalar($expected),
            '>' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            '<' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            '>=' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            '<=' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'in' => is_array($expected) && in_array($this->scalar($actual), array_map($this->scalar(...), $expected), true),
            'not_in' => is_array($expected) && ! in_array($this->scalar($actual), array_map($this->scalar(...), $expected), true),
            'contains' => is_array($actual) && in_array($this->scalar($expected), array_map($this->scalar(...), $actual), true),
            default => false,
        };
    }

    /**
     * Normalise scalars so "5" (string from JSON) and 5 (int from DB) match.
     */
    private function scalar(mixed $value): string|bool|null
    {
        if (is_bool($value) || $value === null) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
