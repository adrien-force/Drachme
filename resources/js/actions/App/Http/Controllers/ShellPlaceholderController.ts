import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::transactions
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
transactionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

transactions.form = transactionsForm

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
export const investments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: investments.url(options),
    method: 'get',
})

investments.definition = {
    methods: ["get","head"],
    url: '/investments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
investments.url = (options?: RouteQueryOptions) => {
    return investments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
investments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: investments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
investments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: investments.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
const investmentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
investmentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::investments
* @see app/Http/Controllers/ShellPlaceholderController.php:33
* @route '/investments'
*/
investmentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investments.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

investments.form = investmentsForm

const ShellPlaceholderController = { transactions, investments }

export default ShellPlaceholderController