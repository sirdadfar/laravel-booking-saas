<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function index(): View
    {
        $tenant = $this->context->requireTenant();

        return view('admin.staff', [
            'tenant' => $tenant,
            'staff' => Staff::query()
                ->with(['services:id,name', 'schedules'])
                ->orderBy('name')
                ->paginate(100),
            'services' => Service::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $data = $this->validated($request);

        DB::transaction(function () use ($data): void {
            $staff = Staff::query()->create($data['attributes']);
            $staff->services()->sync($data['service_ids']);
            $this->replaceSchedules($staff, $data['shifts']);
        });

        return back()->with('status', 'عضو جدید تیم اضافه شد.');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $model = Staff::query()->findOrFail((int) $request->route('staff'));
        $data = $this->validated($request);

        DB::transaction(function () use ($model, $data): void {
            $model->update($data['attributes']);
            $model->services()->sync($data['service_ids']);
            $this->replaceSchedules($model, $data['shifts']);
        });

        return back()->with('status', 'اطلاعات عضو تیم به‌روزرسانی شد.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->context->requireTenant();

        $model = Staff::query()->findOrFail((int) $request->route('staff'));

        if ($model->bookings()->exists()) {
            return back()->withErrors([
                'staff' => 'این عضو سابقهٔ نوبت دارد و حذف نمی‌شود. غیرفعالش کنید.',
            ]);
        }

        $model->services()->detach();
        $model->schedules()->delete();
        $model->delete();

        return back()->with('status', 'عضو تیم حذف شد.');
    }

    /**
     * @return array{attributes: array<string, mixed>, service_ids: array<int, int>, shifts: array<int, array<int, array{0: string, 1: string}>>}
     */
    private function validated(Request $request): array
    {
        $tenantId = $this->context->requireTenant()->getKey();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            // Reject a service id owned by another tenant; the pivot itself
            // carries no tenant_id, so this is the boundary that keeps it clean.
            'service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where('tenant_id', $tenantId),
            ],
            'shifts' => ['nullable', 'array'],
        ]);

        return [
            'attributes' => [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ],
            'service_ids' => Service::query()
                ->whereIn('id', array_map('intval', $request->input('service_ids', [])))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            'shifts' => $this->parseShifts($request->input('shifts', [])),
        ];
    }

    /**
     * "09:00-13:00, 16:00-20:00" for each weekday.
     *
     * @param  array<int|string, mixed>  $input
     * @return array<int, array<int, array{0: string, 1: string}>>
     */
    private function parseShifts(array $input): array
    {
        $parsed = [];

        foreach ($input as $weekday => $value) {
            $weekday = (int) $weekday;

            if ($weekday < 0 || $weekday > 6 || ! is_string($value)) {
                continue;
            }

            $chunks = preg_split('/[\n,،]+/u', $value) ?: [];

            foreach ($chunks as $chunk) {
                $chunk = trim($chunk);

                if ($chunk === '') {
                    continue;
                }

                if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)\s*-\s*([01]\d|2[0-3]):([0-5]\d)$/', $chunk, $m)) {
                    throw ValidationException::withMessages([
                        'shifts.'.$weekday => sprintf('بازهٔ «%s» معتبر نیست. قالب درست: 09:00-13:00', $chunk),
                    ]);
                }

                $start = $m[1].':'.$m[2];
                $end = $m[3].':'.$m[4];

                if ($end <= $start) {
                    throw ValidationException::withMessages([
                        'shifts.'.$weekday => sprintf('پایان بازهٔ «%s» باید بعد از شروعش باشد.', $chunk),
                    ]);
                }

                $parsed[$weekday][] = [$start, $end];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<int, array<int, array{0: string, 1: string}>>  $shifts
     */
    private function replaceSchedules(Staff $staff, array $shifts): void
    {
        $staff->schedules()->delete();

        foreach ($shifts as $weekday => $windows) {
            foreach ($windows as [$start, $end]) {
                StaffSchedule::query()->create([
                    'staff_id' => $staff->getKey(),
                    'weekday' => $weekday,
                    'start_time' => $start,
                    'end_time' => $end,
                ]);
            }
        }
    }
}
