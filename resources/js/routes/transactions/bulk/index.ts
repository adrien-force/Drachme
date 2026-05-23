import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TransactionController::category
* @see app/Http/Controllers/TransactionController.php:135
* @route '/transactions/bulk/category'
*/
export const category = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: category.url(options),
    method: 'post',
})

category.definition = {
    methods: ["post"],
    url: '/transactions/bulk/category',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::category
* @see app/Http/Controllers/TransactionController.php:135
* @route '/transactions/bulk/category'
*/
category.url = (options?: RouteQueryOptions) => {
    return category.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::category
* @see app/Http/Controllers/TransactionController.php:135
* @route '/transactions/bulk/category'
*/
category.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: category.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::category
* @see app/Http/Controllers/TransactionController.php:135
* @route '/transactions/bulk/category'
*/
const categoryForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: category.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::category
* @see app/Http/Controllers/TransactionController.php:135
* @route '/transactions/bulk/category'
*/
categoryForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: category.url(options),
    method: 'post',
})

category.form = categoryForm

/**
* @see \App\Http\Controllers\TransactionController::applyRules
* @see app/Http/Controllers/TransactionController.php:159
* @route '/transactions/bulk/apply-rules'
*/
export const applyRules = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyRules.url(options),
    method: 'post',
})

applyRules.definition = {
    methods: ["post"],
    url: '/transactions/bulk/apply-rules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::applyRules
* @see app/Http/Controllers/TransactionController.php:159
* @route '/transactions/bulk/apply-rules'
*/
applyRules.url = (options?: RouteQueryOptions) => {
    return applyRules.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::applyRules
* @see app/Http/Controllers/TransactionController.php:159
* @route '/transactions/bulk/apply-rules'
*/
applyRules.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyRules.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::applyRules
* @see app/Http/Controllers/TransactionController.php:159
* @route '/transactions/bulk/apply-rules'
*/
const applyRulesForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyRules.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::applyRules
* @see app/Http/Controllers/TransactionController.php:159
* @route '/transactions/bulk/apply-rules'
*/
applyRulesForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyRules.url(options),
    method: 'post',
})

applyRules.form = applyRulesForm

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:183
* @route '/transactions/bulk'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/transactions/bulk',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:183
* @route '/transactions/bulk'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:183
* @route '/transactions/bulk'
*/
destroy.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:183
* @route '/transactions/bulk'
*/
const destroyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:183
* @route '/transactions/bulk'
*/
destroyForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const bulk = {
    category: Object.assign(category, category),
    applyRules: Object.assign(applyRules, applyRules),
    destroy: Object.assign(destroy, destroy),
}

export default bulk