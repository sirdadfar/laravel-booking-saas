<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Staff;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function index(): View
    {
        $tenant = $this->context->requireTenant();

        return view('admin.services', [
            'tenant' => $tenant,
            'services' => Service::query()
                ->withCount('bookings')
                ->with('staff:id,name')
                ->orderBy('name')
                ->get(),
            'staff' => Staff::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $data = $this->validated($request);

        $service = Service::query()->create($data);
        $service->staff()->sync($request->input('staff_ids', []));

        return back()->with('status', 'خدمت جدید اضافه شد.');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $model = Service::query()->findOrFail((int) $request->route('service'));
        $model->update($this->validated($request));
        $model->staff()->sync($request->input('staff_ids', []));

        return back()->with('status', 'خدمت به‌روزرسانی شد.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $model = Service::query()->findOrFail((int) $request->route('service'));

        if ($model->bookings()->exists()) {
            return back()->withErrors([
                'service' => 'این خدمت سابقهٔ نوبت دارد و حذف نمی‌شود. غیرفعالش کنید.',
            ]);
        }

        $model->staff()->detach();
        $model->delete();

        return back()->with('status', 'خدمت حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer'],
        ]);

        $data['buffer_minutes'] = (int) ($data['buffer_minutes'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['color'] = $data['color'] ?? '#0f766e';

        unset($data['staff_ids']);

        return $data;
    }
}
