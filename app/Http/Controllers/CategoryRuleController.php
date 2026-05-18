<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRules\StoreCategoryRuleFromLabelRequest;
use App\Http\Requests\CategoryRules\StoreCategoryRuleRequest;
use App\Http\Requests\CategoryRules\UpdateCategoryRuleRequest;
use App\Models\CategoryRule;
use App\Services\CategoryMatcher;
use App\Services\CategoryRuleService;
use App\Services\CategoryService;
use App\Support\LabelTokenizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CategoryRuleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoryRuleService $rules,
        private readonly CategoryService $categories,
        private readonly CategoryMatcher $matcher,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CategoryRule::class);

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $this->categories->seedDefaultsForUser($user);

        $items = CategoryRule::query()
            ->where('user_id', $user->id)
            ->with('category:id,name,color')
            ->orderByDesc('priority')
            ->orderBy('pattern')
            ->get()
            ->map(fn (CategoryRule $rule): array => $this->serializeRule($rule));

        return Inertia::render('category-rules/category-rules-index', [
            'rules' => $items->values()->all(),
            'categoryOptions' => $this->categories->flatSelectableOptions($user),
        ]);
    }

    public function store(StoreCategoryRuleRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        /** @var array{pattern: string, category_id: int, priority?: int, is_active?: bool} $data */
        $data = $request->validated();
        $data['flow'] = $request->flow();

        try {
            $this->rules->create($user, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.category_rules.created'),
        ]);

        return to_route('category-rules.index');
    }

    public function storeFromLabel(StoreCategoryRuleFromLabelRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->rules->createFromLabelTokens(
                $user,
                (string) $request->input('label'),
                $request->selectedTokens(),
                (int) $request->input('category_id'),
                $request->integer('apply_to_transaction_id') ?: null,
                $request->flow(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.category_rules.created_from_label'),
        ]);

        if ($request->integer('apply_to_transaction_id') > 0) {
            return back();
        }

        return to_route('category-rules.index');
    }

    public function update(UpdateCategoryRuleRequest $request, CategoryRule $categoryRule): RedirectResponse
    {
        $this->authorize('update', $categoryRule);

        /** @var array{pattern?: string, category_id?: int, priority?: int, is_active?: bool} $data */
        $data = $request->validated();

        if ($request->has('flow')) {
            $data['flow'] = $request->flow();
        }

        try {
            $this->rules->update($categoryRule, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.category_rules.updated'),
        ]);

        return to_route('category-rules.index');
    }

    public function destroy(CategoryRule $categoryRule): RedirectResponse
    {
        $this->authorize('delete', $categoryRule);

        $this->rules->delete($categoryRule);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.category_rules.deleted'),
        ]);

        return to_route('category-rules.index');
    }

    public function testMatch(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', CategoryRule::class);

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $label = (string) request()->input('label', '');
        $amountInput = request()->input('amount');
        $amount = is_numeric($amountInput) ? (float) $amountInput : null;
        $matched = $this->matcher->match($user, $label, $amount);

        return response()->json([
            'tokens' => LabelTokenizer::tokenize($label),
            'category' => $matched !== null ? [
                'id' => $matched->id,
                'name' => $matched->name,
                'color' => $matched->color,
            ] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRule(CategoryRule $rule): array
    {
        $category = $rule->category;

        return [
            'id' => $rule->id,
            'pattern' => $rule->pattern,
            'flow' => $rule->flow?->value,
            'priority' => $rule->priority,
            'is_active' => $rule->is_active,
            'category_id' => $rule->category_id,
            'category_name' => $category?->name,
            'category_color' => $category?->color,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mapServiceError(InvalidArgumentException $exception): array
    {
        return match ($exception->getMessage()) {
            'category_rule_pattern_empty' => ['pattern' => __('ui.category_rules.errors.pattern_empty')],
            'category_rule_tokens_invalid' => ['selected_tokens' => __('ui.category_rules.errors.tokens_invalid')],
            'category_rule_category_forbidden' => ['category_id' => __('ui.category_rules.errors.category_forbidden')],
            'category_rule_transaction_forbidden' => ['apply_to_transaction_id' => __('ui.category_rules.errors.transaction_forbidden')],
            default => ['pattern' => __('ui.category_rules.errors.generic')],
        };
    }
}
