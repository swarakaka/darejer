<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers\Documents;

use Darejer\Actions\ButtonAction;
use Darejer\Actions\DeleteAction;
use Darejer\Components\Display;
use Darejer\Components\FileUpload;
use Darejer\Components\SelectComponent;
use Darejer\Components\Table;
use Darejer\Components\Toggle;
use Darejer\Components\TranslatableInput;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\DataTable\DataTable;
use Darejer\Documents\DocumentTemplateRegistry;
use Darejer\Documents\StarterTemplateWriter;
use Darejer\Enums\YesNo;
use Darejer\Forms\Form;
use Darejer\Http\Controllers\DarejerController;
use Darejer\Models\DocumentTemplate;
use Darejer\Routing\Route;
use Darejer\Routing\RoutePattern;
use Darejer\Screen\Screen;
use Darejer\Screen\Section;
use Darejer\Table\Column as TableColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Document Templates. CRUD over uploaded `.docx` designs that the
 * `DocumentRenderer` fills with live voucher data at print time. The list of
 * document types and their token catalogs comes from the host-populated
 * `DocumentTemplateRegistry`.
 */
class DocumentTemplateController extends DarejerController
{
    protected ?string $resource = 'templates';

    protected ?string $routeName = 'darejer.documents.templates';

    protected ?string $routePrefix = 'documents';

    public function index(Request $request): Response
    {
        $this->authorizePermission('system.document_template.viewAny');

        return DataTable::make(DocumentTemplate::class)
            ->title(__darejer('Document Templates'))
            ->breadcrumbs([
                ['label' => __darejer('Setup')],
                ['label' => __darejer('Document Templates')],
            ])
            ->columns([
                Column::make('id')->label('#')->width('80px')->sortable(),
                Column::make('document_type')->label(__darejer('Type'))->sortable()
                    ->displayUsing(fn (DocumentTemplate $t): string => __(DocumentTemplateRegistry::label($t->document_type))),
                Column::make('name')->label(__darejer('Name'))->searchable()
                    ->displayUsing(fn (DocumentTemplate $t): string => $t->getTranslationWithFallback('name')),
                Column::make('paper_size')->label(__darejer('Paper')),
                Column::make('is_default')->label(__darejer('Default'))->badge(YesNo::class),
                Column::make('is_active')->label(__darejer('Active'))->badge(YesNo::class),
                Column::make('created_at')->label(__darejer('Created'))->sortable()->dateTime(),
            ])
            ->filters([
                Filter::select('document_type')->label(__darejer('Type'))
                    ->options($this->typeOptions()),
                Filter::boolean('is_active')->label(__darejer('Active')),
                Filter::trashed(),
            ])
            ->headerActions($this->headerActions())
            ->rowActions([
                RowAction::edit(RoutePattern::row('darejer.documents.templates.edit'))
                    ->canSee('system.document_template.update'),
                RowAction::delete(RoutePattern::row('darejer.documents.templates.destroy'))
                    ->canSee('system.document_template.delete'),
            ])
            ->defaultSort('id', 'desc')
            ->render($request);
    }

    public function create(): Response
    {
        $this->authorizePermission('system.document_template.update');

        return $this->form()
            ->title(__darejer('New Document Template'))
            ->record([
                'paper_size' => 'A4',
                'is_default' => false,
                'is_active' => true,
            ])
            ->save(route('darejer.documents.templates.store'), 'POST')
            ->cancel(route('darejer.documents.templates.index'))
            ->renderAsScreen();
    }

    public function edit(int $template): Response
    {
        $this->authorizePermission('system.document_template.update');

        $record = DocumentTemplate::query()->findOrFail($template);

        return $this->form(creating: false, currentFileUrl: route('darejer.documents.templates.download', $record->id))
            ->title(__darejer('Edit Document Template'))
            ->record(array_merge($record->toArray(), [
                'name' => $record->getFullTranslations('name'),
                'current_file' => basename((string) $record->file_path),
            ]))
            ->save(route('darejer.documents.templates.update', $record->id), 'PUT')
            ->cancel(route('darejer.documents.templates.index'))
            ->addActions([
                DeleteAction::make(__darejer('Delete'))
                    ->url(route('darejer.documents.templates.destroy', $record->id))
                    ->canSee('system.document_template.delete'),
            ])
            ->renderAsScreen();
    }

    public function store(Request $request)
    {
        $this->authorizePermission('system.document_template.update');

        $data = $this->validateRequest($request, creating: true);

        $data['file_path'] = $request->file('file')->store($this->uploadPath(), $this->uploadDisk());

        $record = DocumentTemplate::query()->create($data);

        $this->enforceSingleDefault($record);

        return $this->jsonRedirect(
            route('darejer.documents.templates.index'),
            __darejer('Document template created.'),
            ['id' => $record->id],
        );
    }

    public function update(Request $request, int $template)
    {
        $this->authorizePermission('system.document_template.update');

        $record = DocumentTemplate::query()->findOrFail($template);

        $data = $this->validateRequest($request, creating: false);

        $oldPath = $record->file_path;

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store($this->uploadPath(), $this->uploadDisk());
        }

        $record->update($data);

        // Replacing the file: remove the previous physical .docx.
        if ($request->hasFile('file') && $oldPath && $oldPath !== $record->file_path) {
            Storage::disk($this->uploadDisk())->delete($oldPath);
        }

        $this->enforceSingleDefault($record);

        return $this->jsonRedirect(
            route('darejer.documents.templates.index'),
            __darejer('Document template updated.'),
            ['id' => $record->id],
        );
    }

    public function destroy(int $template)
    {
        $this->authorizePermission('system.document_template.delete');

        $record = DocumentTemplate::query()->findOrFail($template);

        // Remove the stored .docx, then the record.
        if ($record->file_path) {
            Storage::disk($record->disk())->delete($record->file_path);
        }

        $record->delete();

        return redirect()->route('darejer.documents.templates.index');
    }

    /**
     * Download a starter `.docx` skeleton (all placeholders + example tables)
     * for the requested document type. Linked from the index header actions.
     */
    #[Route('GET', 'starter', name: 'starter')]
    public function starter(Request $request): StreamedResponse
    {
        $this->authorizePermission('system.document_template.update');

        $type = (string) $request->query('type');

        abort_unless(DocumentTemplateRegistry::has($type), 404);

        return app(StarterTemplateWriter::class)
            ->download($type, __(DocumentTemplateRegistry::label($type)));
    }

    /**
     * Download the currently-stored `.docx` for a template. Linked from the
     * edit screen so users can see/retrieve what is attached.
     */
    #[Route('GET', '{template}/download', name: 'download')]
    public function download(int $template): StreamedResponse
    {
        $this->authorizePermission('system.document_template.viewAny');

        $record = DocumentTemplate::query()->findOrFail($template);
        $disk = $record->disk();

        abort_unless(Storage::disk($disk)->exists($record->file_path), 404);

        $name = $record->getTranslationWithFallback('name') ?: 'template';

        return Storage::disk($disk)->download($record->file_path, $name.'.docx');
    }

    /**
     * On-screen reference of every `${token}` available per document type.
     * Scalars render as plain placeholders; group tokens belong inside a
     * repeating Word table row.
     */
    #[Route('GET', 'tokens', name: 'tokens')]
    public function tokens(): Response
    {
        $this->authorizePermission('system.document_template.viewAny');

        $record = [];
        $components = [];
        $sections = [];

        foreach (DocumentTemplateRegistry::all() as $type => $definition) {
            $catalog = $definition['provider']::catalog();
            $rows = [];

            foreach ($catalog['scalars'] ?? [] as $token => $label) {
                $rows[] = [
                    'token' => '${'.$token.'}',
                    'field' => __($label),
                    'where' => __darejer('Field'),
                ];
            }

            foreach ($catalog['groups'] ?? [] as $group => $tokens) {
                foreach ($tokens as $token => $label) {
                    $rows[] = [
                        'token' => '${'.$token.'}',
                        'field' => __($label),
                        'where' => __darejer('Table').': '.$group,
                    ];
                }
            }

            $record[$type] = $rows;

            $components[] = Table::make($type)
                ->label(__($definition['label']))
                ->columns([
                    TableColumn::make('token')->label(__darejer('Token'))->width('20rem'),
                    TableColumn::make('field')->label(__darejer('Field')),
                    TableColumn::make('where')->label(__darejer('Where'))->width('16rem'),
                ]);

            $sections[] = Section::make($type)->title(__($definition['label']))->components([$type]);
        }

        return Screen::make(__darejer('Field Reference'))
            ->record($record)
            ->breadcrumbs([
                ['label' => __darejer('Setup')],
                ['label' => __darejer('Document Templates'), 'url' => route('darejer.documents.templates.index')],
                ['label' => __darejer('Field Reference')],
            ])
            ->components($components)
            ->sections($sections)
            ->render();
    }

    public function form(bool $creating = true, ?string $currentFileUrl = null): Form
    {
        $components = [
            SelectComponent::make('document_type')
                ->label(__darejer('Document Type'))
                ->required()
                ->searchable()
                ->options($this->typeOptions()),
            TranslatableInput::make('name')
                ->label(__darejer('Name'))
                ->required(),
            FileUpload::make('file')
                ->label(__darejer('Word template (.docx)'))
                ->accept(['.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->maxSize(10240)
                ->disk($this->uploadDisk())
                ->path($this->uploadPath())
                ->hint($creating
                    ? __darejer('Use ${token} placeholders. Download a starter template from the list page for the full field list.')
                    : __darejer('Leave empty to keep the current file. Uploading replaces it.')),
            SelectComponent::make('paper_size')
                ->label(__darejer('Paper Size'))
                ->options([
                    'A4' => 'A4',
                    'A5' => 'A5',
                    'Letter' => 'Letter',
                    '80mm' => '80mm (receipt)',
                ]),
            Toggle::make('is_default')->label(__darejer('Default for this type')),
            Toggle::make('is_active')->label(__darejer('Active')),
        ];

        $generalFields = ['document_type', 'name', 'file', 'paper_size'];

        // On edit, surface the currently-stored file (the upload widget only
        // shows newly-picked files) as a download link.
        if (! $creating && $currentFileUrl !== null) {
            $components[] = Display::make('current_file')
                ->label(__darejer('Current file'))
                ->link($currentFileUrl, true)
                ->emptyText('—');
            $generalFields = ['document_type', 'name', 'file', 'current_file', 'paper_size'];
        }

        return Form::make('default')
            ->breadcrumbs([
                ['label' => __darejer('Setup')],
                ['label' => __darejer('Document Templates'), 'url' => route('darejer.documents.templates.index')],
            ])
            ->components($components)
            ->sections([
                Section::make('general')->title(__darejer('Template'))->components($generalFields),
                Section::make('status')->title(__darejer('Status'))->components(['is_default', 'is_active']),
            ]);
    }

    /**
     * @return array<int, ButtonAction>
     */
    protected function headerActions(): array
    {
        $actions = [
            ButtonAction::make(__darejer('New Template'))
                ->url(route('darejer.documents.templates.create'))
                ->icon('Plus')
                ->canSee('system.document_template.update'),
            ButtonAction::make(__darejer('Field Reference'))
                ->url(route('darejer.documents.templates.tokens'))
                ->icon('BookOpen')
                ->canSee('system.document_template.viewAny'),
        ];

        foreach (DocumentTemplateRegistry::all() as $type => $definition) {
            $actions[] = ButtonAction::make(__darejer('Starter').': '.__($definition['label']))
                ->url(route('darejer.documents.templates.starter', ['type' => $type]))
                ->icon('Download')
                ->canSee('system.document_template.update');
        }

        return $actions;
    }

    /**
     * Document-type options with labels resolved in the current locale.
     *
     * @return array<string, string>
     */
    protected function typeOptions(): array
    {
        return array_map(fn (string $label): string => __($label), DocumentTemplateRegistry::options());
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRequest(Request $request, bool $creating): array
    {
        return $request->validate([
            'document_type' => ['required', 'string', Rule::in(array_keys(DocumentTemplateRegistry::all()))],
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'paper_size' => ['nullable', 'string', 'max:16'],
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'file' => [$creating ? 'required' : 'nullable', 'file', 'extensions:docx', 'max:10240'],
        ]);
    }

    /**
     * Keep at most one default template per (document_type, company scope).
     */
    protected function enforceSingleDefault(DocumentTemplate $record): void
    {
        if (! $record->is_default) {
            return;
        }

        DocumentTemplate::query()
            ->where('document_type', $record->document_type)
            ->where('company_id', $record->company_id)
            ->where('id', '!=', $record->id)
            ->update(['is_default' => false]);
    }

    protected function uploadDisk(): string
    {
        return (string) config('darejer.uploads.disk', 'public');
    }

    protected function uploadPath(): string
    {
        return trim((string) config('darejer.uploads.path', 'uploads'), '/').'/document-templates';
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->can($permission) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')))) {
            abort(403);
        }
    }
}
