<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Services\BulkUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view goods', only: ['index', 'show']),
            new Middleware('permission:create goods', only: ['create', 'store', 'quickStore', 'bulkUpload', 'bulkUploadTemplate']),
            new Middleware('permission:edit goods', only: ['edit', 'update']),
            new Middleware('permission:delete goods', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $items = Good::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Goods/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Good::create($validated);

        return redirect()->back()->with('success', 'Goods created successfully.');
    }

    public function show(Good $good)
    {
        return Inertia::render('Goods/Show', [
            'good' => $good,
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $good = Good::create($validated);

        return response()->json([
            'good' => [
                'id' => $good->id,
                'name' => $good->name,
            ],
        ], 201);
    }

    public function update(Request $request, Good $good)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $good->update($validated);

        return redirect()->back()->with('success', 'Goods updated successfully.');
    }

    public function destroy(Good $good)
    {
        $good->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $good->delete();

        return redirect()->route('goods.index')->with('success', 'Goods deleted successfully.');
    }

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: ['Name'],
            fileName: 'goods_bulk_upload_template.csv'
        );
    }

    public function bulkUpload(Request $request, BulkUploadService $bulkUploadService): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'extensions:csv,xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
            'file.extensions' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
        ]);

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: Good::class,
            columnSpecs: [
                'name' => [
                    'label' => 'Name',
                    'required' => true,
                    'aliases' => ['name', 'good_name', 'goods_name', 'item_name'],
                    'rules' => ['required', 'string', 'max:255'],
                    'unique' => true,
                ],
            ]
        );

        return response()->json($result);
    }
}
