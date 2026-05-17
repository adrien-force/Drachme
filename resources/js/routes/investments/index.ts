import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import snapshots from './snapshots'
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

const investments = {
    index: Object.assign(index, index),
    refreshPrices: Object.assign(refreshPrices, refreshPrices),
    snapshots: Object.assign(snapshots, snapshots),
}

export default investments