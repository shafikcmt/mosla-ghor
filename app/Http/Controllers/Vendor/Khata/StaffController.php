<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Models\User;
use App\Support\KhataStaff;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** Shop owner manages staff logins and what each one may do in the খাতা. */
class StaffController extends KhataBaseController
{
    public function index()
    {
        $vendor = $this->vendor();
        // Removed staff (no phone, inactive) stay only as the name on their old entries.
        $staff = User::where('staff_vendor_id', $vendor->id)
            ->where(fn ($q) => $q->where('staff_active', true)->orWhereNotNull('phone'))
            ->orderByDesc('staff_active')->orderBy('name')->get();

        return view('vendor.khata.staff.index', compact('vendor', 'staff'));
    }

    public function create()
    {
        return view('vendor.khata.staff.form', ['vendor' => $this->vendor(), 'staff' => new User(['staff_permissions' => ['sale', 'payment'], 'staff_active' => true])]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $data = $this->validated($request);
        $staff = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'password_set_at' => now(),
            'role' => 'vendor',
            'is_admin' => false,
            'staff_vendor_id' => $vendor->id,
            'staff_permissions' => $data['permissions'] ?? [],
            'staff_active' => true,
        ]);
        Log::info('Khata staff created', ['vendor_id' => $vendor->id, 'staff_id' => $staff->id, 'by' => Auth::id()]);

        return redirect()->route('vendor.khata.staff.index')
            ->with('success', "{$staff->name} যোগ হয়েছে। লগইন: {$staff->phone} ও আপনার দেওয়া পাসওয়ার্ড — moslamart.com/vendor/login");
    }

    public function edit(User $staff)
    {
        return view('vendor.khata.staff.form', ['vendor' => $this->vendor(), 'staff' => $this->ownStaff($staff)]);
    }

    public function update(Request $request, User $staff)
    {
        $this->ownStaff($staff);
        $data = $this->validated($request, $staff);
        $staff->fill([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'staff_permissions' => $data['permissions'] ?? [],
            'staff_active' => $request->boolean('staff_active'),
        ]);
        if (! empty($data['password'])) {
            $staff->password = $data['password'];
            $staff->password_set_at = now();
        }
        $staff->save();
        Log::info('Khata staff updated', ['vendor_id' => $staff->staff_vendor_id, 'staff_id' => $staff->id, 'by' => Auth::id()]);

        return redirect()->route('vendor.khata.staff.index')->with('success', "{$staff->name}-এর তথ্য আপডেট হয়েছে।");
    }

    public function destroy(User $staff)
    {
        $this->ownStaff($staff);
        // Close the login but keep the user row, so entries they wrote still show their name.
        $staff->forceFill(['staff_active' => false, 'phone' => null, 'email' => null, 'staff_permissions' => [],
            'password' => \Illuminate\Support\Str::random(40), 'remember_token' => null])->save();
        Log::info('Khata staff removed', ['staff_id' => $staff->id, 'by' => Auth::id()]);

        return redirect()->route('vendor.khata.staff.index')->with('success', 'স্টাফ মুছে ফেলা হয়েছে।');
    }

    private function ownStaff(User $staff): User
    {
        abort_unless($staff->staff_vendor_id === $this->vendor()->id, 404);

        return $staff;
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')->ignore($staff?->id)],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(KhataStaff::PERMISSIONS))],
        ], [
            'name.required' => 'স্টাফের নাম দিন।',
            'phone.required' => 'মোবাইল নম্বর দিন — এটি দিয়েই লগইন করবে।',
            'phone.regex' => 'সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।',
            'phone.unique' => 'এই নম্বরে আগে থেকেই একটি অ্যাকাউন্ট আছে — অন্য নম্বর দিন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
            'permissions.*.in' => 'অনুমতির তালিকা সঠিক নয়।',
        ]);
    }
}
