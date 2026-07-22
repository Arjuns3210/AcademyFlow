<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Which permission applies depends on the submitted date, not just the
     * route — marking today is attendance,add, correcting a past date is
     * attendance,edit. This is the first Form Request in the app where
     * authorize() does real work instead of deferring entirely to route
     * middleware, since route middleware can't express "the required
     * action depends on submitted data" with its single-action-string
     * design. Still exclusively PermissionService underneath — not a new
     * authorization path.
     */
    public function authorize(): bool
    {
        $isToday = $this->input('attendance_date') === now()->toDateString();

        return $isToday
            ? $this->user()->canModule('attendance', 'add')
            : $this->user()->canModule('attendance', 'edit');
    }

    public function rules(): array
    {
        return [
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'batch_id' => ['nullable', 'integer'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', 'in:present,absent,late,leave'],
        ];
    }
}
