import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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

const ShellPlaceholderController = { investments }

export default ShellPlaceholderController