# Contributing — Documentation Sync Rule

This package treats documentation as a first-class citizen of the codebase. **Every change to package source MUST be reflected in the docs in the same commit.**

If you only read one section, read this:

---

## The Rules

### 1. Before any code change
Read the existing docs for that component, action, or PHP class. Match their tone and structure.

### 2. When adding a new component / action
Create its doc file in the same change.
- Component → `docs/components/<kebab-case-name>.md`
- Action → `docs/actions/<kebab-case-name>.md`
- Add an entry to the section index (`docs/components/README.md` or `docs/actions/README.md`).

### 3. When modifying a component / action
If you add, remove, or rename a prop, slot, event, or fluent method — update the doc file in the same change.

### 4. When deleting a component / action
- Remove its doc file.
- Remove it from the section index.
- Add a `### Removed` entry in `CHANGELOG.md`.

### 5. When changing the public PHP API
Update [`api-reference/php-api.md`](api-reference/php-api.md). The public surface includes:
- `Darejer\Screen\Screen` builder methods
- `Darejer\Forms\Form` builder methods
- `Darejer\Http\Controllers\DarejerController` properties + helpers
- `Darejer\Routing\Route` attribute
- `Darejer\Routing\RoutePattern` static methods
- `Darejer\Components\BaseComponent` shared API (`canSee`, `dependOn`, etc.)
- `Darejer\Actions\BaseAction` shared API
- Any new `Darejer\Components\*`, `Darejer\Actions\*`, `Darejer\Screen\*`, `Darejer\Forms\*` class

### 6. Always update `CHANGELOG.md`
Add a bullet under `Unreleased` using Keep-a-Changelog headings: **Added / Changed / Deprecated / Removed / Fixed / Security**.

### 7. Final consistency check
Before opening the PR, verify:
- [ ] Every component in `src/Components/` has a `docs/components/<name>.md`.
- [ ] Every action in `src/Actions/` (excluding `Fortify/`) has a `docs/actions/<name>.md`.
- [ ] No prop / slot / event / fluent method appears in source but not in its doc.
- [ ] No doc references an API that no longer exists.
- [ ] All code examples compile / are syntactically valid.

---

## Per-component / per-action template

Each `docs/components/*.md` and `docs/actions/*.md` file contains the same sections, in this order:

```markdown
# ComponentName

> One-sentence description of what it does.

## Import / usage

```php
use Darejer\Components\ComponentName;
````

## Props

| Method | Type | Default | Description |
|---|---|---|---|
| `->propA(value)` | `string` | `''` | What it does |

## Slots
None / list of named slots and their purpose.

## Events
None / events the backend can react to (route + payload).

## Examples

### Basic
```php
ComponentName::make('field')->label('Field')
```

### Intermediate
```php
// Add a more involved configuration
```

### Real-world
```php
// Pull from an actual screen
```

## Accessibility
Keyboard support + ARIA notes.

## Related
- [`OtherComponent`](other-component.md)
```

Always show **PHP examples first**. Add Vue/TS only when the section is genuinely about extending the package.

---

## Markdown style

- Code fences must use the correct language tag: `php`, `blade`, `vue`, `bash`, `json`, `ts`.
- Tables for props / slots / events / config keys.
- Short sentences. Imperative voice.
- Cross-link with relative paths (`../actions/button.md`), never absolute or full URLs.
- Don't write documentation for internal classes (`Darejer\Routing\ControllerRouteRegistrar`, `Darejer\Data\DataQuery`, etc.) unless they're in the public surface listed above.

---

## When in doubt

Ask: "If a Laravel developer reads this without ever opening a `.vue` file, can they use the feature?" If the answer is no, the doc is incomplete.
