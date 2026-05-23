import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TransactionTriageController::applyRules
* @see app/Http/Controllers/TransactionTriageController.php:105
* @route '/transactions/triage/apply-rules'
*/
export const applyRules = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyRules.url(options),
    method: 'post',
})

applyRules.definition = {
    methods: ["post"],
    url: '/transactions/triage/apply-rules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionTriageController::applyRules
* @see app/Http/Controllers/TransactionTriageController.php:105
* @route '/transactions/triage/apply-rules'
*/
applyRules.url = (options?: RouteQueryOptions) => {
    return applyRules.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionTriageController::applyRules
* @see app/Http/Controllers/TransactionTriageController.php:105
* @route '/transactions/triage/apply-rules'
*/
applyRules.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyRules.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionTriageController::applyRules
* @see app/Http/Controllers/TransactionTriageController.php:105
* @route '/transactions/triage/apply-rules'
*/
const applyRulesForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyRules.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionTriageController::applyRules
* @see app/Http/Controllers/TransactionTriageController.php:105
* @route '/transactions/triage/apply-rules'
*/
applyRulesForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyRules.url(options),
    method: 'post',
})

applyRules.form = applyRulesForm

/**
* @see \App\Http\Controllers\TransactionTriageController::process
* @see app/Http/Controllers/TransactionTriageController.php:54
* @route '/transactions/{transaction}/triage'
*/
export const process = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/transactions/{transaction}/triage',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionTriageController::process
* @see app/Http/Controllers/TransactionTriageController.php:54
* @route '/transactions/{transaction}/triage'
*/
process.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return process.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionTriageController::process
* @see app/Http/Controllers/TransactionTriageController.php:54
* @route '/transactions/{transaction}/triage'
*/
process.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionTriageController::process
* @see app/Http/Controllers/TransactionTriageController.php:54
* @route '/transactions/{transaction}/triage'
*/
const processForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionTriageController::process
* @see app/Http/Controllers/TransactionTriageController.php:54
* @route '/transactions/{transaction}/triage'
*/
processForm.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: process.url(args, options),
    method: 'post',
})

process.form = processForm

const triage = {
    applyRules: Object.assign(applyRules, applyRules),
    process: Object.assign(process, process),
}

export default triage