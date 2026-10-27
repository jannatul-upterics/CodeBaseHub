<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    /**
     * Display a listing of page and site layout templates.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', '');

        $query = Template::query();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('template_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $templates = $query->orderBy('template_name')
            ->paginate(12)
            ->withQueryString();

        return view('templates.index', compact('templates', 'search', 'status'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('templates.create');
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = isset($data['status']) ? (int) $data['status'] : 1;

        $template = Template::create($data);

        return redirect()
            ->route('templates.index')
            ->with('success', "Template '{$template->template_name}' created successfully.");
    }

    /**
     * Display the specified template details.
     */
    public function show(Template $template): View
    {
        return view('templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(Template $template): View
    {
        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified template in storage.
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = isset($data['status']) ? (int) $data['status'] : 0;

        $template->update($data);

        return redirect()
            ->route('templates.show', $template)
            ->with('success', "Template '{$template->template_name}' updated successfully.");
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(Template $template): RedirectResponse
    {
        $name = $template->template_name;
        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with('success', "Template '{$name}' deleted successfully.");
    }
}
