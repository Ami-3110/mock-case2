<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStampCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fixed_clock_in'  => ['required', 'date_format:H:i'],
            'fixed_clock_out' => ['required', 'date_format:H:i'],
            'fixed_breaks'    => ['array'],
            'fixed_breaks.*.break_start' => ['nullable', 'date_format:H:i'],
            'fixed_breaks.*.break_end'   => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $in  = $this->input('fixed_clock_in');
            $out = $this->input('fixed_clock_out');

            if ($in && $out && $in > $out) {
                $validator->errors()->add('fixed_clock_in', '出勤時間もしくは退勤時間が不適切な値です。');
                $validator->errors()->add('fixed_clock_out', '出勤時間もしくは退勤時間が不適切な値です。');
            }

            $breaks = $this->input('fixed_breaks', []);
            foreach ($breaks as $i => $b) {
                $bs = $b['break_start'] ?? null;
                $be = $b['break_end'] ?? null;

                if (($bs && !$be) || (!$bs && $be)) {
                        $validator->errors()->add("fixed_breaks.$i.break_start", '休憩開始と終了は両方入力してください。');
                        $validator->errors()->add("fixed_breaks.$i.break_end", '休憩開始と終了は両方入力してください。');
                    }
                if ($bs && ($in && $bs < $in || $out && $bs > $out)) {
                    $validator->errors()->add("fixed_breaks.$i.break_start", '休憩時間が勤務時間外です。');
                }

                if ($be && ($in && $be < $in || $out && $be > $out)) {
                    $validator->errors()->add("fixed_breaks.$i.break_end", '休憩時間が勤務時間外です。');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'fixed_clock_in.required'  => '出勤時間は必須です。',
            'fixed_clock_in.date_format' => '出勤時間の形式が正しくありません。',
            'fixed_clock_out.required' => '退勤時間は必須です。',
            'fixed_clock_out.date_format' => '退勤時間の形式が正しくありません。',
            'fixed_breaks.*.break_start.date_format' => '休憩開始時間の形式が正しくありません。',
            'fixed_breaks.*.break_end.date_format'   => '休憩終了時間の形式が正しくありません。',
            'fixed_breaks.*.break_start.required_with' => '休憩開始と終了は両方入力してください。',
            'fixed_breaks.*.break_end.required_with'   => '休憩開始と終了は両方入力してください。',
            'reason.required' => '備考を記入してください。',
        ];
    }
}

