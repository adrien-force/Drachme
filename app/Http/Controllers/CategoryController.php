<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Categories\DeleteCategoryRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $this->categories->seedDefaultsForUser($user);

        $flat = Category::query()
            ->where('user_id', $user->id)
            ->withCount('transactions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => $this->serializeCategory($category));

        return Inertia::render('categories/categories-index', [
            'categories' => $flat->values()->all(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        /** @var array{name: string, parent_id?: int|null, color?: string|null, icon?: string|null} $data */
        $data = $request->validated();

        try {
            $this->categories->create($user, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.categories.created'),
        ]);

        return to_route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        /** @var array{name: string, parent_id?: int|null, color?: string|null, icon?: string|null} $data */
        $data = $request->validated();

        try {
            $this->categories->update($category, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.categories.updated'),
        ]);

        return to_route('categories.index');
    }

    public function destroy(DeleteCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $mergeId = $request->integer('merge_into_category_id');
        $mergeTarget = $mergeId > 0
            ? Category::query()->where('user_id', $category->user_id)->find($mergeId)
            : null;

        try {
            $this->categories->delete($category, $mergeTarget);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.categories.deleted'),
        ]);

        return to_route('categories.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'color' => $category->color,
            'icon' => $category->icon,
            'sort_order' => (int) $category->sort_order,
            'is_system' => $category->is_system,
            'is_uncategorized' => $category->isUncategorized(),
            'transactions_count' => (int) $category->transactions_count,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mapServiceError(InvalidArgumentException $exception): array
    {
        $key = $exception->getMessage();

        return match ($key) {
            'category_max_depth' => ['parent_id' => __('ui.categories.errors.max_depth')],
            'category_merge_required' => ['category' => __('ui.categories.errors.merge_required')],
            'category_has_children' => ['category' => __('ui.categories.errors.has_children')],
            'category_system_protected' => ['category' => __('ui.categories.errors.system_protected')],
            'category_circular_parent' => ['parent_id' => __('ui.categories.errors.circular_parent')],
            'category_parent_forbidden' => ['parent_id' => __('ui.categories.errors.parent_forbidden')],
            'category_merge_forbidden' => ['merge_into_category_id' => __('ui.categories.errors.merge_forbidden')],
            'category_merge_same' => ['merge_into_category_id' => __('ui.categories.errors.merge_same')],
            default => ['category' => __('ui.categories.errors.generic')],
        };
    }
}
