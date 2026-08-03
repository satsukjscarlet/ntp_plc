<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateMachineProductStandardRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('machine-product-standards.update') ?? false; }
    public function rules(): array
    {
        $id=$this->route('machineProductStandard')?->id;
        return [
            'machine_id' => ['required','integer','exists:machines,id', Rule::unique('machine_product_standards')->ignore($id)->where(fn($q)=>$q->where('product_id',$this->input('product_id'))->whereNull('deleted_at'))],
            'product_id' => ['required','integer','exists:products,id'],
            'standard_rate' => ['required','numeric','gt:0'],
            'rate_unit' => ['required','string','max:30'],
            'effective_from' => ['nullable','date'],
            'effective_to' => ['nullable','date','after_or_equal:effective_from'],
            'is_active' => ['nullable','boolean'],
            'notes' => ['nullable','string','max:2000'],
        ];
    }
    protected function prepareForValidation(): void { $this->merge(['is_active'=>$this->boolean('is_active')]); }
}
