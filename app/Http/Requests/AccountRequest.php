<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
            'role' => 'required|string|in:admin,customer,employee',
            'customer_id' => [
                'nullable',
                'exists:customers,id'
            ],
            'employee_id' => [
                'nullable',
                'exists:employees,id'
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required when role is customer.',
            'employee_id.required' => 'Employee ID is required when role is employee.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role is required for account creation.',
            'role.in' => 'Role must be one of: admin, customer, employee.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $role = request('role');
            $customerId = request('customer_id');
            $employeeId = request('employee_id');

            // Validate logic constraints
            if ($role === 'customer') {
                if (!$customerId) {
                    $validator->errors()->add('customer_id', 'Customer ID is required when role is customer.');
                }
                if ($employeeId) {
                    $validator->errors()->add('employee_id', 'Employee ID should not be provided when role is customer.');
                }
            }

            if ($role === 'employee') {
                if (!$employeeId) {
                    $validator->errors()->add('employee_id', 'Employee ID is required when role is employee.');
                }
                if ($customerId) {
                    $validator->errors()->add('customer_id', 'Customer ID should not be provided when role is employee.');
                }
            }

            if ($role === 'admin' && ($customerId || $employeeId)) {
                $validator->errors()->add('role', 'Admin role should not have customer_id or employee_id.');
            }
        });
    }
}
