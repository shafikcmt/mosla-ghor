@php
    $isEditingOld = (string) old('courier_basic_id') === (string) $courier->id;
@endphp
<form method="POST" action="{{ route('admin.couriers.update', $courier) }}" class="space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="courier_basic_id" value="{{ $courier->id }}">
    @if($isEditingOld && $errors->any())
    <div class="text-sm text-red-700" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @foreach(['name' => 'Name', 'slug' => 'Slug'] as $field => $label)
    <div>
        <label for="basic-{{ $courier->id }}-{{ $field }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
        <input id="basic-{{ $courier->id }}-{{ $field }}" type="text" name="{{ $field }}" value="{{ $isEditingOld ? old($field) : $courier->{$field} }}" maxlength="100" {{ $field === 'name' ? 'required' : '' }} class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    @endforeach
    <div>
        <label for="basic-{{ $courier->id }}-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select id="basic-{{ $courier->id }}-status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
            <option value="{{ $value }}" {{ ($isEditingOld ? old('status') : $courier->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @foreach(['vendor_allowed' => 'Vendor allowed', 'is_default' => 'Default courier'] as $field => $label)
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="{{ $field }}" value="1" {{ ($isEditingOld ? old($field, false) : $courier->{$field}) ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]"> {{ $label }}
    </label>
    @endforeach
    <div>
        <label for="basic-{{ $courier->id }}-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea id="basic-{{ $courier->id }}-notes" name="notes" rows="3" maxlength="1000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $isEditingOld ? old('notes') : $courier->notes }}</textarea>
    </div>
    <button class="bg-[#14532d] text-white text-sm px-5 py-2 rounded-lg hover:bg-[#0d3520]">Save Settings</button>
</form>
