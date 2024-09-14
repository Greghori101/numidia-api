<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\MarkSheet;
use Illuminate\Http\Request;

class MarkSheetController extends Controller
{



    public function show($id)
    {
        return MarkSheet::with(['marks', 'level', 'student'])->findOrFail($id);
    }

    public function create(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'season' => ['required', 'string', 'in:Spring,Summer,Autumn,Winter'],
            'mark' => ['required', 'numeric', 'between:0,20'],
            'notes' => ['nullable', 'string'],
            'level_id' => ['required', 'exists:levels,id'],
            'student_id' => ['required', 'exists:students,id'],
        ]);
        MarkSheet::create([
            'year' => $request->year,
            'season' => $request->season,
            'mark' => $request->mark,
            'notes' => $request->notes,
            'level_id' => $request->level_id,
            'student_id' => $request->student_id,
        ]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'season' => ['required', 'string', 'in:Spring,Summer,Autumn,Winter'],
            'mark' => ['required', 'numeric', 'between:0,20'],
            'notes' => ['nullable', 'string'],
            'level_id' => ['required', 'exists:levels,id'],
            'student_id' => ['required', 'exists:students,id'],
        ]);
        $mark_sheet  = MarkSheet::findOrFail($id);
        $mark_sheet->update([
            'year' => $request->year,
            'season' => $request->season,
            'mark' => $request->mark,
            'notes' => $request->notes,
            'level_id' => $request->level_id,
            'student_id' => $request->student_id,
        ]);
    }

    public function delete($id)
    {
        $mark_sheet  = MarkSheet::findOrFail($id);
        $mark_sheet->delete();
    }

    public function add_mark(Request $request, $id)
    {
        $request->validate([
            'module' => ['required', 'string'],
            'coefficient' => ['required', 'numeric', 'min:0'],
            'mark' => ['required', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);
        $mark_sheet  = MarkSheet::findOrFail($id);
        $mark_sheet->marks()->save(new Mark([
            'module' => $request->module,
            'coefficient' => $request->coefficient,
            'mark' => $request->mark,
            'notes' => $request->notes,
        ]));
    }
    public function update_mark(Request $request, $id)
    {
        $request->validate([
            'module' => ['required', 'string'],
            'coefficient' => ['required', 'numeric', 'min:0'],
            'mark' => ['required', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);
        $mark = Mark::findOrFail($id);
        $mark->update([
            'module' => $request->module,
            'coefficient' => $request->coefficient,
            'mark' => $request->mark,
            'notes' => $request->notes,
        ]);
    }
    public function delete_mark($id)
    {
        $mark = Mark::findOrFail($id);
        $mark->delete();
    }
}
