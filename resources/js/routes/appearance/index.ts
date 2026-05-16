import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
const editForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
editForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::edit
* @see app/Http/Controllers/Settings/AppearanceController.php:17
* @route '/settings/appearance'
*/
editForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\Settings\AppearanceController::update
* @see app/Http/Controllers/Settings/AppearanceController.php:25
* @route '/settings/appearance'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Settings\AppearanceController::update
* @see app/Http/Controllers/Settings/AppearanceController.php:25
* @route '/settings/appearance'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AppearanceController::update
* @see app/Http/Controllers/Settings/AppearanceController.php:25
* @route '/settings/appearance'
*/
update.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::update
* @see app/Http/Controllers/Settings/AppearanceController.php:25
* @route '/settings/appearance'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::update
* @see app/Http/Controllers/Settings/AppearanceController.php:25
* @route '/settings/appearance'
*/
updateForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Settings\AppearanceController::reset
* @see app/Http/Controllers/Settings/AppearanceController.php:43
* @route '/settings/appearance/reset'
*/
export const reset = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reset.url(options),
    method: 'post',
})

reset.definition = {
    methods: ["post"],
    url: '/settings/appearance/reset',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AppearanceController::reset
* @see app/Http/Controllers/Settings/AppearanceController.php:43
* @route '/settings/appearance/reset'
*/
reset.url = (options?: RouteQueryOptions) => {
    return reset.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AppearanceController::reset
* @see app/Http/Controllers/Settings/AppearanceController.php:43
* @route '/settings/appearance/reset'
*/
reset.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reset.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::reset
* @see app/Http/Controllers/Settings/AppearanceController.php:43
* @route '/settings/appearance/reset'
*/
const resetForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reset.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::reset
* @see app/Http/Controllers/Settings/AppearanceController.php:43
* @route '/settings/appearance/reset'
*/
resetForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reset.url(options),
    method: 'post',
})

reset.form = resetForm

const appearance = {
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    reset: Object.assign(reset, reset),
}

export default appearance