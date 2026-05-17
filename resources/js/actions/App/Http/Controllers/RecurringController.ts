import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/recurring',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RecurringController::index
* @see app/Http/Controllers/RecurringController.php:31
* @route '/recurring'
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
* @see \App\Http\Controllers\RecurringController::confirm
* @see app/Http/Controllers/RecurringController.php:46
* @route '/recurring/confirm'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/recurring/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringController::confirm
* @see app/Http/Controllers/RecurringController.php:46
* @route '/recurring/confirm'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringController::confirm
* @see app/Http/Controllers/RecurringController.php:46
* @route '/recurring/confirm'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringController::confirm
* @see app/Http/Controllers/RecurringController.php:46
* @route '/recurring/confirm'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringController::confirm
* @see app/Http/Controllers/RecurringController.php:46
* @route '/recurring/confirm'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\RecurringController::dismiss
* @see app/Http/Controllers/RecurringController.php:82
* @route '/recurring/dismiss'
*/
export const dismiss = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dismiss.url(options),
    method: 'post',
})

dismiss.definition = {
    methods: ["post"],
    url: '/recurring/dismiss',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringController::dismiss
* @see app/Http/Controllers/RecurringController.php:82
* @route '/recurring/dismiss'
*/
dismiss.url = (options?: RouteQueryOptions) => {
    return dismiss.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringController::dismiss
* @see app/Http/Controllers/RecurringController.php:82
* @route '/recurring/dismiss'
*/
dismiss.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dismiss.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringController::dismiss
* @see app/Http/Controllers/RecurringController.php:82
* @route '/recurring/dismiss'
*/
const dismissForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dismiss.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringController::dismiss
* @see app/Http/Controllers/RecurringController.php:82
* @route '/recurring/dismiss'
*/
dismissForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dismiss.url(options),
    method: 'post',
})

dismiss.form = dismissForm

/**
* @see \App\Http\Controllers\RecurringController::destroy
* @see app/Http/Controllers/RecurringController.php:94
* @route '/recurring/{recurringPattern}'
*/
export const destroy = (args: { recurringPattern: number | { id: number } } | [recurringPattern: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/recurring/{recurringPattern}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\RecurringController::destroy
* @see app/Http/Controllers/RecurringController.php:94
* @route '/recurring/{recurringPattern}'
*/
destroy.url = (args: { recurringPattern: number | { id: number } } | [recurringPattern: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { recurringPattern: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { recurringPattern: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            recurringPattern: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        recurringPattern: typeof args.recurringPattern === 'object'
        ? args.recurringPattern.id
        : args.recurringPattern,
    }

    return destroy.definition.url
            .replace('{recurringPattern}', parsedArgs.recurringPattern.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringController::destroy
* @see app/Http/Controllers/RecurringController.php:94
* @route '/recurring/{recurringPattern}'
*/
destroy.delete = (args: { recurringPattern: number | { id: number } } | [recurringPattern: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\RecurringController::destroy
* @see app/Http/Controllers/RecurringController.php:94
* @route '/recurring/{recurringPattern}'
*/
const destroyForm = (args: { recurringPattern: number | { id: number } } | [recurringPattern: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringController::destroy
* @see app/Http/Controllers/RecurringController.php:94
* @route '/recurring/{recurringPattern}'
*/
destroyForm.delete = (args: { recurringPattern: number | { id: number } } | [recurringPattern: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const RecurringController = { index, confirm, dismiss, destroy }

export default RecurringController