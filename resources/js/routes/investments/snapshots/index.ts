import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestmentsController::destroy
* @see app/Http/Controllers/InvestmentsController.php:125
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
export const destroy = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/investments/snapshots/{portfolioSnapshot}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\InvestmentsController::destroy
* @see app/Http/Controllers/InvestmentsController.php:125
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroy.url = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{portfolioSnapshot}', parsedArgs.portfolioSnapshot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestmentsController::destroy
* @see app/Http/Controllers/InvestmentsController.php:125
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroy.delete = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\InvestmentsController::destroy
* @see app/Http/Controllers/InvestmentsController.php:125
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
const destroyForm = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestmentsController::destroy
* @see app/Http/Controllers/InvestmentsController.php:125
* @route '/investments/snapshots/{portfolioSnapshot}'
*/
destroyForm.delete = (args: { portfolioSnapshot: number | { id: number } } | [portfolioSnapshot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const snapshots = {
    destroy: Object.assign(destroy, destroy),
}

export default snapshots