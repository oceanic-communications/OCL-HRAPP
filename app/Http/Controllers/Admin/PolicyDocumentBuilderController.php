<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Models\InductionSubClause;
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

    public function editor(Request $request, InductionPolicy $policy): View
    {
        $this->authorizeReadInductionPolicies();

        $policy->load([
            'versions' => fn ($q) => $q
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(1),
            'versions.sections' => fn ($q) => $q
                ->whereNull('archived_at')
                ->orderBy('sort_order')
                ->with(['activeSubClauses']),
        ]);

        $version = $policy->versions->first();
        $clauses = $version?->sections ?? collect();
        $activeNode = $this->resolveActiveNode($request, $policy, $clauses);
        $scheme = $this->numbering->schemeForPolicy($policy);

        $clauseLabels = [];
        foreach ($clauses as $i => $clause) {
            $clauseLabels[$clause->id] = $this->numbering->formatClauseLabel($i + 1, $clause, $policy);
        }

        $subLabels = [];
        foreach ($clauses as $i => $clause) {
            foreach ($clause->activeSubClauses as $j => $sub) {
                $subLabels[$sub->id] = $this->numbering->formatSubClauseLabel($i + 1, $j + 1, $sub, $clause, $policy);
            }
        }

        return view('admin.induction.builder.editor', [
            'policy' => $policy,
            'clauses' => $clauses,
            'activeNode' => $activeNode,
            'scheme' => $scheme,
            'clauseLabels' => $clauseLabels,
            'subLabels' => $subLabels,
            'sectionLabel' => $this->numbering->formatSectionLabel(1, $policy),
        ]);
    }

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

    public function updateSubClauseNumbering(Request $request, InductionPolicy $policy, InductionSection $section, InductionSubClause $sub_clause): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();

        $version = $policy->ensureEditableVersion();
        abort_unless($section->induction_policy_version_id === $version->id, 404);
        abort_unless($sub_clause->induction_section_id === $section->id, 404);

        $data = $request->validate([
            'number_prefix' => ['nullable', 'string', 'max:32'],
            'numbering_style' => ['required', 'string', 'max:32'],
            'number_separator' => ['required', 'string', 'max:16'],
        ]);

        $sub_clause->forceFill($data)->save();

        return redirect()
            ->route('admin.induction.policies.builder', [
                'policy' => $policy,
                'node' => 'sub-clause-'.$sub_clause->id,
            ])
            ->with('success', 'Sub-clause numbering updated.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, InductionSection>  $clauses
     * @return array{type: string, id: int, model: InductionPolicy|InductionSection|InductionSubClause, title: string, body: string, label: string}
     */
    private function resolveActiveNode(Request $request, InductionPolicy $policy, $clauses): array
    {
        $node = (string) $request->query('node', '');

        if (str_starts_with($node, 'sub-clause-')) {
            $id = (int) str_replace('sub-clause-', '', $node);
            foreach ($clauses as $i => $clause) {
                $sub = $clause->activeSubClauses->firstWhere('id', $id);
                if ($sub) {
                    return [
                        'type' => 'sub_clause',
                        'id' => $sub->id,
                        'model' => $sub,
                        'clause' => $clause,
                        'title' => $sub->title,
                        'body' => $sub->body,
                        'label' => $this->numbering->formatSubClauseLabel(
                            $i + 1,
                            $clause->activeSubClauses->values()->search(fn ($s) => $s->id === $sub->id) + 1,
                            $sub,
                            $clause,
                            $policy
                        ),
                    ];
                }
            }
        }

        if (str_starts_with($node, 'clause-')) {
            $id = (int) str_replace('clause-', '', $node);
            $clause = $clauses->firstWhere('id', $id);
            if ($clause) {
                $idx = $clauses->search(fn ($c) => $c->id === $clause->id);

                return [
                    'type' => 'clause',
                    'id' => $clause->id,
                    'model' => $clause,
                    'title' => $clause->title,
                    'body' => $clause->body,
                    'label' => $this->numbering->formatClauseLabel($idx + 1, $clause, $policy),
                ];
            }
        }

        foreach ($clauses as $i => $clause) {
            $sub = $clause->activeSubClauses->first();
            if ($sub) {
                return [
                    'type' => 'sub_clause',
                    'id' => $sub->id,
                    'model' => $sub,
                    'clause' => $clause,
                    'title' => $sub->title,
                    'body' => $sub->body,
                    'label' => $this->numbering->formatSubClauseLabel($i + 1, 1, $sub, $clause, $policy),
                ];
            }
        }

        $firstClause = $clauses->first();
        if ($firstClause) {
            return [
                'type' => 'clause',
                'id' => $firstClause->id,
                'model' => $firstClause,
                'title' => $firstClause->title,
                'body' => $firstClause->body,
                'label' => $this->numbering->formatClauseLabel(1, $firstClause, $policy),
            ];
        }

        return [
            'type' => 'policy',
            'id' => $policy->id,
            'model' => $policy,
            'title' => $policy->name,
            'body' => '',
            'label' => $this->numbering->formatSectionLabel(1, $policy),
        ];
    }
}
