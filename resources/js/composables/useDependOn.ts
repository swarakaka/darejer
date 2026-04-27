import type { DependOnCondition, DependOnRule } from '@/types/darejer'

/**
 * Compare two values by operator. All string-equality comparisons coerce both
 * sides to string so `1` and `'1'` (e.g. a form radio value) match.
 */
function evaluateCondition(
    condition: DependOnCondition,
    formData: Record<string, unknown>,
): boolean {
    const fieldValue = formData[condition.field]
    const ruleValue  = condition.value

    switch (condition.operator) {
        case 'eq':
            return String(fieldValue ?? '') === String(ruleValue ?? '')

        case 'neq':
            return String(fieldValue ?? '') !== String(ruleValue ?? '')

        case 'gt':  return Number(fieldValue) >  Number(ruleValue)
        case 'gte': return Number(fieldValue) >= Number(ruleValue)
        case 'lt':  return Number(fieldValue) <  Number(ruleValue)
        case 'lte': return Number(fieldValue) <= Number(ruleValue)

        case 'in':
            return Array.isArray(ruleValue)
                ? ruleValue.map(String).includes(String(fieldValue ?? ''))
                : false

        case 'notIn':
            return Array.isArray(ruleValue)
                ? !ruleValue.map(String).includes(String(fieldValue ?? ''))
                : true

        case 'contains':
            return String(fieldValue ?? '').toLowerCase()
                .includes(String(ruleValue ?? '').toLowerCase())

        case 'notEmpty':
            return fieldValue !== null
                && fieldValue !== undefined
                && fieldValue !== ''
                && fieldValue !== false
                && fieldValue !== 0
                && !(Array.isArray(fieldValue) && fieldValue.length === 0)

        case 'empty':
            return fieldValue === null
                || fieldValue === undefined
                || fieldValue === ''
                || fieldValue === false
                || fieldValue === 0
                || (Array.isArray(fieldValue) && fieldValue.length === 0)

        default:
            return true
    }
}

/**
 * Evaluates a `DependOnRule` against a form data bag. Returns true when the
 * owning component / section / action should be visible. A null rule means
 * "always visible."
 */
export function evaluateDependOn(
    rule: DependOnRule | null | undefined,
    formData: Record<string, unknown>,
): boolean {
    if (!rule) return true

    if (rule.conditions && Array.isArray(rule.conditions)) {
        const logic = rule.logic ?? 'and'
        return logic === 'or'
            ? rule.conditions.some(c => evaluateCondition(c, formData))
            : rule.conditions.every(c => evaluateCondition(c, formData))
    }

    if (rule.field) {
        return evaluateCondition(
            {
                field:    rule.field,
                operator: rule.operator ?? 'eq',
                value:    rule.value,
            },
            formData,
        )
    }

    return true
}

export function useDependOn() {
    return { evaluateDependOn }
}
