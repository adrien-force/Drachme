import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
export const show = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/positions/{position}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
show.url = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { position: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return show.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
show.get = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
show.head = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
const showForm = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
showForm.get = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
showForm.head = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\PositionController::refreshPrice
* @see app/Http/Controllers/PositionController.php:71
* @route '/positions/{position}/refresh-price'
*/
export const refreshPrice = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshPrice.url(args, options),
    method: 'post',
})

refreshPrice.definition = {
    methods: ["post"],
    url: '/positions/{position}/refresh-price',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PositionController::refreshPrice
* @see app/Http/Controllers/PositionController.php:71
* @route '/positions/{position}/refresh-price'
*/
refreshPrice.url = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { position: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return refreshPrice.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::refreshPrice
* @see app/Http/Controllers/PositionController.php:71
* @route '/positions/{position}/refresh-price'
*/
refreshPrice.post = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshPrice.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::refreshPrice
* @see app/Http/Controllers/PositionController.php:71
* @route '/positions/{position}/refresh-price'
*/
const refreshPriceForm = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshPrice.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::refreshPrice
* @see app/Http/Controllers/PositionController.php:71
* @route '/positions/{position}/refresh-price'
*/
refreshPriceForm.post = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshPrice.url(args, options),
    method: 'post',
})

refreshPrice.form = refreshPriceForm

/**
* @see \App\Http\Controllers\PositionController::refreshHistory
* @see app/Http/Controllers/PositionController.php:109
* @route '/positions/{position}/refresh-history'
*/
export const refreshHistory = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshHistory.url(args, options),
    method: 'post',
})

refreshHistory.definition = {
    methods: ["post"],
    url: '/positions/{position}/refresh-history',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PositionController::refreshHistory
* @see app/Http/Controllers/PositionController.php:109
* @route '/positions/{position}/refresh-history'
*/
refreshHistory.url = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { position: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return refreshHistory.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::refreshHistory
* @see app/Http/Controllers/PositionController.php:109
* @route '/positions/{position}/refresh-history'
*/
refreshHistory.post = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refreshHistory.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::refreshHistory
* @see app/Http/Controllers/PositionController.php:109
* @route '/positions/{position}/refresh-history'
*/
const refreshHistoryForm = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshHistory.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::refreshHistory
* @see app/Http/Controllers/PositionController.php:109
* @route '/positions/{position}/refresh-history'
*/
refreshHistoryForm.post = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refreshHistory.url(args, options),
    method: 'post',
})

refreshHistory.form = refreshHistoryForm

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
export const index = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/accounts/{account}/positions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
index.url = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { account: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { account: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            account: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        account: typeof args.account === 'object'
        ? args.account.id
        : args.account,
    }

    return index.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
index.get = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
index.head = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
const indexForm = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
indexForm.get = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:35
* @route '/accounts/{account}/positions'
*/
indexForm.head = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:147
* @route '/accounts/{account}/positions'
*/
export const store = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/accounts/{account}/positions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:147
* @route '/accounts/{account}/positions'
*/
store.url = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { account: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { account: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            account: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        account: typeof args.account === 'object'
        ? args.account.id
        : args.account,
    }

    return store.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:147
* @route '/accounts/{account}/positions'
*/
store.post = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:147
* @route '/accounts/{account}/positions'
*/
const storeForm = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:147
* @route '/accounts/{account}/positions'
*/
storeForm.post = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:178
* @route '/accounts/{account}/positions/{position}'
*/
export const update = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/accounts/{account}/positions/{position}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:178
* @route '/accounts/{account}/positions/{position}'
*/
update.url = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            account: args[0],
            position: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        account: typeof args.account === 'object'
        ? args.account.id
        : args.account,
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return update.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:178
* @route '/accounts/{account}/positions/{position}'
*/
update.put = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:178
* @route '/accounts/{account}/positions/{position}'
*/
const updateForm = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:178
* @route '/accounts/{account}/positions/{position}'
*/
updateForm.put = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:204
* @route '/accounts/{account}/positions/{position}'
*/
export const destroy = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/accounts/{account}/positions/{position}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:204
* @route '/accounts/{account}/positions/{position}'
*/
destroy.url = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            account: args[0],
            position: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        account: typeof args.account === 'object'
        ? args.account.id
        : args.account,
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return destroy.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:204
* @route '/accounts/{account}/positions/{position}'
*/
destroy.delete = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:204
* @route '/accounts/{account}/positions/{position}'
*/
const destroyForm = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:204
* @route '/accounts/{account}/positions/{position}'
*/
destroyForm.delete = (args: { account: number | { id: number }, position: number | { id: number } } | [account: number | { id: number }, position: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const PositionController = { show, refreshPrice, refreshHistory, index, store, update, destroy }

export default PositionController