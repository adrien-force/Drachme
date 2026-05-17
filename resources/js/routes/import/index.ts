import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:34
* @route '/import'
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
* @see \App\Http\Controllers\ImportController::store
* @see app/Http/Controllers/ImportController.php:52
* @route '/import'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/import',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::store
* @see app/Http/Controllers/ImportController.php:52
* @route '/import'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::store
* @see app/Http/Controllers/ImportController.php:52
* @route '/import'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::store
* @see app/Http/Controllers/ImportController.php:52
* @route '/import'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::store
* @see app/Http/Controllers/ImportController.php:52
* @route '/import'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
export const show = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/import/{importBatch}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
show.url = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importBatch: typeof args.importBatch === 'object'
        ? args.importBatch.id
        : args.importBatch,
    }

    return show.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
show.get = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
show.head = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
const showForm = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
showForm.get = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::show
* @see app/Http/Controllers/ImportController.php:69
* @route '/import/{importBatch}'
*/
showForm.head = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ImportController::parse
* @see app/Http/Controllers/ImportController.php:78
* @route '/import/{importBatch}/parse'
*/
export const parse = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: parse.url(args, options),
    method: 'post',
})

parse.definition = {
    methods: ["post"],
    url: '/import/{importBatch}/parse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::parse
* @see app/Http/Controllers/ImportController.php:78
* @route '/import/{importBatch}/parse'
*/
parse.url = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importBatch: typeof args.importBatch === 'object'
        ? args.importBatch.id
        : args.importBatch,
    }

    return parse.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::parse
* @see app/Http/Controllers/ImportController.php:78
* @route '/import/{importBatch}/parse'
*/
parse.post = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: parse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::parse
* @see app/Http/Controllers/ImportController.php:78
* @route '/import/{importBatch}/parse'
*/
const parseForm = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: parse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::parse
* @see app/Http/Controllers/ImportController.php:78
* @route '/import/{importBatch}/parse'
*/
parseForm.post = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: parse.url(args, options),
    method: 'post',
})

parse.form = parseForm

/**
* @see \App\Http\Controllers\ImportController::commit
* @see app/Http/Controllers/ImportController.php:93
* @route '/import/{importBatch}/commit'
*/
export const commit = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commit.url(args, options),
    method: 'post',
})

commit.definition = {
    methods: ["post"],
    url: '/import/{importBatch}/commit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::commit
* @see app/Http/Controllers/ImportController.php:93
* @route '/import/{importBatch}/commit'
*/
commit.url = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importBatch: typeof args.importBatch === 'object'
        ? args.importBatch.id
        : args.importBatch,
    }

    return commit.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::commit
* @see app/Http/Controllers/ImportController.php:93
* @route '/import/{importBatch}/commit'
*/
commit.post = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: commit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::commit
* @see app/Http/Controllers/ImportController.php:93
* @route '/import/{importBatch}/commit'
*/
const commitForm = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::commit
* @see app/Http/Controllers/ImportController.php:93
* @route '/import/{importBatch}/commit'
*/
commitForm.post = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: commit.url(args, options),
    method: 'post',
})

commit.form = commitForm

/**
* @see \App\Http\Controllers\ImportController::destroy
* @see app/Http/Controllers/ImportController.php:111
* @route '/import/{importBatch}'
*/
export const destroy = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/import/{importBatch}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ImportController::destroy
* @see app/Http/Controllers/ImportController.php:111
* @route '/import/{importBatch}'
*/
destroy.url = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importBatch: typeof args.importBatch === 'object'
        ? args.importBatch.id
        : args.importBatch,
    }

    return destroy.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::destroy
* @see app/Http/Controllers/ImportController.php:111
* @route '/import/{importBatch}'
*/
destroy.delete = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ImportController::destroy
* @see app/Http/Controllers/ImportController.php:111
* @route '/import/{importBatch}'
*/
const destroyForm = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::destroy
* @see app/Http/Controllers/ImportController.php:111
* @route '/import/{importBatch}'
*/
destroyForm.delete = (args: { importBatch: number | { id: number } } | [importBatch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const importMethod = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    parse: Object.assign(parse, parse),
    commit: Object.assign(commit, commit),
    destroy: Object.assign(destroy, destroy),
}

export default importMethod