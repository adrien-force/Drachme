import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/providers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::index
* @see app/Http/Controllers/ImportProviderController.php:33
* @route '/providers'
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
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/providers/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::create
* @see app/Http/Controllers/ImportProviderController.php:48
* @route '/providers/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\ImportProviderController::detectDateFormat
* @see app/Http/Controllers/ImportProviderController.php:73
* @route '/providers/detect-date-format'
*/
export const detectDateFormat = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: detectDateFormat.url(options),
    method: 'post',
})

detectDateFormat.definition = {
    methods: ["post"],
    url: '/providers/detect-date-format',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportProviderController::detectDateFormat
* @see app/Http/Controllers/ImportProviderController.php:73
* @route '/providers/detect-date-format'
*/
detectDateFormat.url = (options?: RouteQueryOptions) => {
    return detectDateFormat.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::detectDateFormat
* @see app/Http/Controllers/ImportProviderController.php:73
* @route '/providers/detect-date-format'
*/
detectDateFormat.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: detectDateFormat.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::detectDateFormat
* @see app/Http/Controllers/ImportProviderController.php:73
* @route '/providers/detect-date-format'
*/
const detectDateFormatForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: detectDateFormat.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::detectDateFormat
* @see app/Http/Controllers/ImportProviderController.php:73
* @route '/providers/detect-date-format'
*/
detectDateFormatForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: detectDateFormat.url(options),
    method: 'post',
})

detectDateFormat.form = detectDateFormatForm

/**
* @see \App\Http\Controllers\ImportProviderController::preview
* @see app/Http/Controllers/ImportProviderController.php:88
* @route '/providers/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/providers/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportProviderController::preview
* @see app/Http/Controllers/ImportProviderController.php:88
* @route '/providers/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::preview
* @see app/Http/Controllers/ImportProviderController.php:88
* @route '/providers/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::preview
* @see app/Http/Controllers/ImportProviderController.php:88
* @route '/providers/preview'
*/
const previewForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::preview
* @see app/Http/Controllers/ImportProviderController.php:88
* @route '/providers/preview'
*/
previewForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

preview.form = previewForm

/**
* @see \App\Http\Controllers\ImportProviderController::store
* @see app/Http/Controllers/ImportProviderController.php:108
* @route '/providers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/providers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportProviderController::store
* @see app/Http/Controllers/ImportProviderController.php:108
* @route '/providers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::store
* @see app/Http/Controllers/ImportProviderController.php:108
* @route '/providers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::store
* @see app/Http/Controllers/ImportProviderController.php:108
* @route '/providers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::store
* @see app/Http/Controllers/ImportProviderController.php:108
* @route '/providers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
export const show = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/providers/{importProvider}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
show.url = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importProvider: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importProvider: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importProvider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importProvider: typeof args.importProvider === 'object'
        ? args.importProvider.id
        : args.importProvider,
    }

    return show.definition.url
            .replace('{importProvider}', parsedArgs.importProvider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
show.get = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
show.head = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
const showForm = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
showForm.get = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::show
* @see app/Http/Controllers/ImportProviderController.php:55
* @route '/providers/{importProvider}'
*/
showForm.head = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
export const edit = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/providers/{importProvider}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
edit.url = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importProvider: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importProvider: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importProvider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importProvider: typeof args.importProvider === 'object'
        ? args.importProvider.id
        : args.importProvider,
    }

    return edit.definition.url
            .replace('{importProvider}', parsedArgs.importProvider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
edit.get = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
edit.head = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
const editForm = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
editForm.get = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportProviderController::edit
* @see app/Http/Controllers/ImportProviderController.php:64
* @route '/providers/{importProvider}/edit'
*/
editForm.head = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\ImportProviderController::update
* @see app/Http/Controllers/ImportProviderController.php:133
* @route '/providers/{importProvider}'
*/
export const update = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/providers/{importProvider}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ImportProviderController::update
* @see app/Http/Controllers/ImportProviderController.php:133
* @route '/providers/{importProvider}'
*/
update.url = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importProvider: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importProvider: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importProvider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importProvider: typeof args.importProvider === 'object'
        ? args.importProvider.id
        : args.importProvider,
    }

    return update.definition.url
            .replace('{importProvider}', parsedArgs.importProvider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::update
* @see app/Http/Controllers/ImportProviderController.php:133
* @route '/providers/{importProvider}'
*/
update.put = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ImportProviderController::update
* @see app/Http/Controllers/ImportProviderController.php:133
* @route '/providers/{importProvider}'
*/
const updateForm = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::update
* @see app/Http/Controllers/ImportProviderController.php:133
* @route '/providers/{importProvider}'
*/
updateForm.put = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\ImportProviderController::destroy
* @see app/Http/Controllers/ImportProviderController.php:155
* @route '/providers/{importProvider}'
*/
export const destroy = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/providers/{importProvider}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ImportProviderController::destroy
* @see app/Http/Controllers/ImportProviderController.php:155
* @route '/providers/{importProvider}'
*/
destroy.url = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importProvider: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importProvider: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            importProvider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        importProvider: typeof args.importProvider === 'object'
        ? args.importProvider.id
        : args.importProvider,
    }

    return destroy.definition.url
            .replace('{importProvider}', parsedArgs.importProvider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportProviderController::destroy
* @see app/Http/Controllers/ImportProviderController.php:155
* @route '/providers/{importProvider}'
*/
destroy.delete = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ImportProviderController::destroy
* @see app/Http/Controllers/ImportProviderController.php:155
* @route '/providers/{importProvider}'
*/
const destroyForm = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportProviderController::destroy
* @see app/Http/Controllers/ImportProviderController.php:155
* @route '/providers/{importProvider}'
*/
destroyForm.delete = (args: { importProvider: number | { id: number } } | [importProvider: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const providers = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    detectDateFormat: Object.assign(detectDateFormat, detectDateFormat),
    preview: Object.assign(preview, preview),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default providers