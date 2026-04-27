<?php

/**
 * PHP port of the useDependOn composable used for validating operator logic
 * in isolation. Keeps the PHP and TypeScript implementations in lockstep.
 */
function evaluateSimple(array $rule, array $data): bool
{
    $field = $rule['field'];
    $operator = $rule['operator'] ?? 'eq';
    $value = $rule['value'] ?? null;
    $fv = $data[$field] ?? null;

    return match ($operator) {
        'eq' => (string) $fv === (string) $value,
        'neq' => (string) $fv !== (string) $value,
        'gt' => (float) $fv > (float) $value,
        'gte' => (float) $fv >= (float) $value,
        'lt' => (float) $fv < (float) $value,
        'lte' => (float) $fv <= (float) $value,
        'in' => in_array((string) $fv, array_map('strval', (array) $value), true),
        'notIn' => ! in_array((string) $fv, array_map('strval', (array) $value), true),
        'notEmpty' => ! in_array($fv, [null, '', false, 0, []], true),
        'empty' => in_array($fv, [null, '', false, 0, []], true),
        default => true,
    };
}

it('eq operator works', function () {
    expect(evaluateSimple(['field' => 'status', 'operator' => 'eq', 'value' => 'archived'], ['status' => 'archived']))
        ->toBeTrue();
    expect(evaluateSimple(['field' => 'status', 'operator' => 'eq', 'value' => 'archived'], ['status' => 'active']))
        ->toBeFalse();
});

it('notEmpty operator works', function () {
    expect(evaluateSimple(['field' => 'price', 'operator' => 'notEmpty'], ['price' => '100']))->toBeTrue();
    expect(evaluateSimple(['field' => 'price', 'operator' => 'notEmpty'], ['price' => '']))->toBeFalse();
    expect(evaluateSimple(['field' => 'price', 'operator' => 'notEmpty'], ['price' => null]))->toBeFalse();
    expect(evaluateSimple(['field' => 'price', 'operator' => 'notEmpty'], ['price' => 0]))->toBeFalse();
});

it('in operator works', function () {
    expect(evaluateSimple(['field' => 'type', 'operator' => 'in', 'value' => ['physical', 'digital']], ['type' => 'physical']))
        ->toBeTrue();
    expect(evaluateSimple(['field' => 'type', 'operator' => 'in', 'value' => ['physical', 'digital']], ['type' => 'service']))
        ->toBeFalse();
});

it('gt operator works', function () {
    expect(evaluateSimple(['field' => 'price', 'operator' => 'gt', 'value' => 10], ['price' => 20]))->toBeTrue();
    expect(evaluateSimple(['field' => 'price', 'operator' => 'gt', 'value' => 10], ['price' => 5]))->toBeFalse();
});
