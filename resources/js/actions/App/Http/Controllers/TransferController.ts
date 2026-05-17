import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/transfers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransferController::index
* @see app/Http/Controllers/TransferController.php:28
* @route '/transfers'
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
* @see \App\Http\Controllers\TransferController::accept
* @see app/Http/Controllers/TransferController.php:41
* @route '/transfers/accept'
*/
export const accept = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

accept.definition = {
    methods: ["post"],
    url: '/transfers/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransferController::accept
* @see app/Http/Controllers/TransferController.php:41
* @route '/transfers/accept'
*/
accept.url = (options?: RouteQueryOptions) => {
    return accept.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransferController::accept
* @see app/Http/Controllers/TransferController.php:41
* @route '/transfers/accept'
*/
accept.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::accept
* @see app/Http/Controllers/TransferController.php:41
* @route '/transfers/accept'
*/
const acceptForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::accept
* @see app/Http/Controllers/TransferController.php:41
* @route '/transfers/accept'
*/
acceptForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(options),
    method: 'post',
})

accept.form = acceptForm

/**
* @see \App\Http\Controllers\TransferController::dismiss
* @see app/Http/Controllers/TransferController.php:63
* @route '/transfers/dismiss'
*/
export const dismiss = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dismiss.url(options),
    method: 'post',
})

dismiss.definition = {
    methods: ["post"],
    url: '/transfers/dismiss',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransferController::dismiss
* @see app/Http/Controllers/TransferController.php:63
* @route '/transfers/dismiss'
*/
dismiss.url = (options?: RouteQueryOptions) => {
    return dismiss.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransferController::dismiss
* @see app/Http/Controllers/TransferController.php:63
* @route '/transfers/dismiss'
*/
dismiss.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dismiss.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::dismiss
* @see app/Http/Controllers/TransferController.php:63
* @route '/transfers/dismiss'
*/
const dismissForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dismiss.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::dismiss
* @see app/Http/Controllers/TransferController.php:63
* @route '/transfers/dismiss'
*/
dismissForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: dismiss.url(options),
    method: 'post',
})

dismiss.form = dismissForm

/**
* @see \App\Http\Controllers\TransferController::store
* @see app/Http/Controllers/TransferController.php:84
* @route '/transfers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/transfers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransferController::store
* @see app/Http/Controllers/TransferController.php:84
* @route '/transfers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransferController::store
* @see app/Http/Controllers/TransferController.php:84
* @route '/transfers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::store
* @see app/Http/Controllers/TransferController.php:84
* @route '/transfers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransferController::store
* @see app/Http/Controllers/TransferController.php:84
* @route '/transfers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const TransferController = { index, accept, dismiss, store }

export default TransferController