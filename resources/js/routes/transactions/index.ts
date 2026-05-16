import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShellPlaceholderController::index
* @see app/Http/Controllers/ShellPlaceholderController.php:18
* @route '/transactions'
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

const transactions = {
    index: Object.assign(index, index),
}

export default transactions