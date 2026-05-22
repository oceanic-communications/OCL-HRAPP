<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Services\Induction\InductionNumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyDocumentBuilderController extends Controller
{
    use AuthorizesPortalAccess;

    public function __construct(
        private readonly InductionNumberingService $numbering,
    ) {}

    public function numberingSettings(Request $request): View
    {
        $this->authorizeReadInductionPolicies();

        $policy = $request->filled('policy')
            ? InductionPolicy::query()->find($request->integer('policy'))
            : InductionPolicy::query()->where('is_active', true)->orderBy('name')->first();
        $scheme = $policy ? $this->numbering->schemeForPolicy($policy) : $this->numbering->defaultScheme();

        return view('admin.settings.numbering', [
            'policy' => $policy,
            'scheme' => $scheme,
            'policies' => InductionPolicy::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function updateNumberingScheme(Request $request, InductionPolicy $policy = null): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();

        $data = $request->validate([
            'apply_all' => ['sometimes', 'boolean'],
            'scheme' => ['required', 'array'],
            'scheme.section.style' => ['required', 'string'],
            'scheme.section.separator' => ['required', 'string', 'max:16'],
            'scheme.section.start' => ['nullable', 'string', 'max:16'],
            'scheme.clause.style' => ['required', 'string'],
            'scheme.clause.separator' => ['required', 'string', 'max:16'],
            'scheme.clause.start' => ['nullable', 'string', 'max:16'],
            'scheme.clause.inherit_preview' => ['nullable', 'string', 'max:32'],
            'scheme.sub_clause.style' => ['required', 'string'],
            'scheme.sub_clause.separator' => ['required', 'string', 'max:16'],
            'scheme.sub_clause.prefix' => ['nullable', 'string', 'max:32'],
            'scheme.sub_clause.start' => ['nullable', 'string', 'max:16'],
        ]);

        $scheme = $data['scheme'];

        if ($request->boolean('apply_all')) {
            InductionPolicy::query()->update(['numbering_scheme' => $scheme]);

            return redirect()
                ->route('admin.settings.numbering')
                ->with('success', 'Numbering scheme applied to all policies.');
        }

        if ($policy === null) {
            return redirect()
                ->route('admin.settings.numbering')
                ->withErrors(['policy' => 'Select a policy to save numbering settings.']);
        }

        $policy->forceFill(['numbering_scheme' => $scheme])->save();

        return redirect()
            ->route('admin.settings.numbering', ['policy' => $policy->id])
            ->with('success', 'Numbering scheme saved for '.$policy->name.'.');
    }
}
