<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function listData()
    {
        $languages = Language::orderBy('language_name')->get();

        return view('admin.languages', compact('languages'));
    }

    public function create()
    {
        return view('admin/languages-create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateLanguage($request);

        Language::create($validated);

        return redirect()->route('admin.language')
            ->with('success', 'Language created successfully.');
    }

    public function edit($id)
    {
        $language = Language::findOrFail($id);

        return view('admin/languages-edit', compact('language'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateLanguage($request);

        $language = Language::findOrFail($id);
        $language->update($validated);

        return redirect()->route('admin.language')
            ->with('success', 'Language updated successfully.');
    }

    public function delete($id)
    {
        Language::findOrFail($id)->delete();

        return redirect()->route('admin.language')
            ->with('success', 'Language deleted successfully.');
    }

    private function validateLanguage(Request $request)
    {
        $data = $request->validate([
            'language_name' => 'required|string|max:255',
        ]);

        $data['chrPublish'] = $request->boolean('chrPublish') ? 'Y' : 'N';

        return $data;
    }
}
