import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/category-rules',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::index
* @see app/Http/Controllers/CategoryRuleController.php:32
* @route '/category-rules'
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
* @see \App\Http\Controllers\CategoryRuleController::store
* @see app/Http/Controllers/CategoryRuleController.php:57
* @route '/category-rules'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/category-rules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::store
* @see app/Http/Controllers/CategoryRuleController.php:57
* @route '/category-rules'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::store
* @see app/Http/Controllers/CategoryRuleController.php:57
* @route '/category-rules'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::store
* @see app/Http/Controllers/CategoryRuleController.php:57
* @route '/category-rules'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::store
* @see app/Http/Controllers/CategoryRuleController.php:57
* @route '/category-rules'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\CategoryRuleController::storeFromLabel
* @see app/Http/Controllers/CategoryRuleController.php:82
* @route '/category-rules/from-label'
*/
export const storeFromLabel = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeFromLabel.url(options),
    method: 'post',
})

storeFromLabel.definition = {
    methods: ["post"],
    url: '/category-rules/from-label',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::storeFromLabel
* @see app/Http/Controllers/CategoryRuleController.php:82
* @route '/category-rules/from-label'
*/
storeFromLabel.url = (options?: RouteQueryOptions) => {
    return storeFromLabel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::storeFromLabel
* @see app/Http/Controllers/CategoryRuleController.php:82
* @route '/category-rules/from-label'
*/
storeFromLabel.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeFromLabel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::storeFromLabel
* @see app/Http/Controllers/CategoryRuleController.php:82
* @route '/category-rules/from-label'
*/
const storeFromLabelForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeFromLabel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::storeFromLabel
* @see app/Http/Controllers/CategoryRuleController.php:82
* @route '/category-rules/from-label'
*/
storeFromLabelForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeFromLabel.url(options),
    method: 'post',
})

storeFromLabel.form = storeFromLabelForm

/**
* @see \App\Http\Controllers\CategoryRuleController::testMatch
* @see app/Http/Controllers/CategoryRuleController.php:153
* @route '/category-rules/test-match'
*/
export const testMatch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testMatch.url(options),
    method: 'post',
})

testMatch.definition = {
    methods: ["post"],
    url: '/category-rules/test-match',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::testMatch
* @see app/Http/Controllers/CategoryRuleController.php:153
* @route '/category-rules/test-match'
*/
testMatch.url = (options?: RouteQueryOptions) => {
    return testMatch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::testMatch
* @see app/Http/Controllers/CategoryRuleController.php:153
* @route '/category-rules/test-match'
*/
testMatch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testMatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::testMatch
* @see app/Http/Controllers/CategoryRuleController.php:153
* @route '/category-rules/test-match'
*/
const testMatchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testMatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::testMatch
* @see app/Http/Controllers/CategoryRuleController.php:153
* @route '/category-rules/test-match'
*/
testMatchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testMatch.url(options),
    method: 'post',
})

testMatch.form = testMatchForm

/**
* @see \App\Http\Controllers\CategoryRuleController::update
* @see app/Http/Controllers/CategoryRuleController.php:114
* @route '/category-rules/{categoryRule}'
*/
export const update = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/category-rules/{categoryRule}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::update
* @see app/Http/Controllers/CategoryRuleController.php:114
* @route '/category-rules/{categoryRule}'
*/
update.url = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { categoryRule: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { categoryRule: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            categoryRule: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        categoryRule: typeof args.categoryRule === 'object'
        ? args.categoryRule.id
        : args.categoryRule,
    }

    return update.definition.url
            .replace('{categoryRule}', parsedArgs.categoryRule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::update
* @see app/Http/Controllers/CategoryRuleController.php:114
* @route '/category-rules/{categoryRule}'
*/
update.put = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::update
* @see app/Http/Controllers/CategoryRuleController.php:114
* @route '/category-rules/{categoryRule}'
*/
const updateForm = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::update
* @see app/Http/Controllers/CategoryRuleController.php:114
* @route '/category-rules/{categoryRule}'
*/
updateForm.put = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\CategoryRuleController::destroy
* @see app/Http/Controllers/CategoryRuleController.php:139
* @route '/category-rules/{categoryRule}'
*/
export const destroy = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/category-rules/{categoryRule}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\CategoryRuleController::destroy
* @see app/Http/Controllers/CategoryRuleController.php:139
* @route '/category-rules/{categoryRule}'
*/
destroy.url = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { categoryRule: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { categoryRule: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            categoryRule: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        categoryRule: typeof args.categoryRule === 'object'
        ? args.categoryRule.id
        : args.categoryRule,
    }

    return destroy.definition.url
            .replace('{categoryRule}', parsedArgs.categoryRule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CategoryRuleController::destroy
* @see app/Http/Controllers/CategoryRuleController.php:139
* @route '/category-rules/{categoryRule}'
*/
destroy.delete = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::destroy
* @see app/Http/Controllers/CategoryRuleController.php:139
* @route '/category-rules/{categoryRule}'
*/
const destroyForm = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CategoryRuleController::destroy
* @see app/Http/Controllers/CategoryRuleController.php:139
* @route '/category-rules/{categoryRule}'
*/
destroyForm.delete = (args: { categoryRule: number | { id: number } } | [categoryRule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const CategoryRuleController = { index, store, storeFromLabel, testMatch, update, destroy }

export default CategoryRuleController