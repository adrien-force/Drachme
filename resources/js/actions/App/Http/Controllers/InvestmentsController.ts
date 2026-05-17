import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/investments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestmentsController::index
* @see app/Http/Controllers/InvestmentsController.php:34
* @route '/investments'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\InvestmentsController::refreshPrices
* @see app/Http/Controllers/InvestmentsController.php:83
* @route '/investments/refresh-prices'
*/
export const refreshPrices = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshPrices.url(options),
    method: 'post',
})

refreshPrices.definition = {
    methods: ["post"],
    url: '/investments/refresh-prices',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestmentsController::refreshPrices
* @see app/Http/Controllers/InvestmentsController.php:83
* @route '/investments/refresh-prices'
*/
refreshPrices.url = (options?: RouteQueryOptions) => {
    return refreshPrices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestmentsController::refreshPrices
* @see app/Http/Controllers/InvestmentsController.php:83
* @route '/investments/refresh-prices'
*/
refreshPrices.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshPrices.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestmentsController::refreshPrices
* @see app/Http/Controllers/InvestmentsController.php:83
* @route '/investments/refresh-prices'
*/
const refreshPricesForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshPrices.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestmentsController::refreshPrices
* @see app/Http/Controllers/InvestmentsController.php:83
* @route '/investments/refresh-prices'
*/
refreshPricesForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshPrices.url(options),
    method: 'post',
})

refreshPrices.form = refreshPricesForm

/**
* @see \App\Http\Controllers\InvestmentsController::destroySnapshot
* @see app/Http/Controllers/InvestmentsController.php:126
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
export const destroySnapshot = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroySnapshot.url(args, options),
    method: 'delete',
})

destroySnapshot.definition = {
    methods: ["delete"],
    url: '/investments/snapshots/{portfolioSnapshot}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\InvestmentsController::destroySnapshot
* @see app/Http/Controllers/InvestmentsController.php:126
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroySnapshot.url = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { portfolioSnapshot: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { portfolioSnapshot: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            portfolioSnapshot: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        portfolioSnapshot: typeof args.portfolioSnapshot === 'object'
        ? args.portfolioSnapshot.id
        : args.portfolioSnapshot,
    }

    return destroySnapshot.definition.url
            .replace('{portfolioSnapshot}', parsedArgs.portfolioSnapshot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestmentsController::destroySnapshot
* @see app/Http/Controllers/InvestmentsController.php:126
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroySnapshot.delete = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroySnapshot.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\InvestmentsController::destroySnapshot
* @see app/Http/Controllers/InvestmentsController.php:126
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
const destroySnapshotForm = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroySnapshot.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestmentsController::destroySnapshot
* @see app/Http/Controllers/InvestmentsController.php:126
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroySnapshotForm.delete = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroySnapshot.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroySnapshot.form = destroySnapshotForm

const InvestmentsController = { index, refreshPrices, destroySnapshot }

export default InvestmentsController