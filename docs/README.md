# Darejer Documentation

> **Write only PHP. Get a complete enterprise frontend.**

Darejer is a Laravel + Inertia + Vue admin platform package. You declare screens, components, and actions in PHP. The frontend renders them through a single generic `Screen.vue` page — no per-screen Vue code is ever written.

These docs describe the **public PHP API only**. Vue / TypeScript appears only when you need to extend the package itself.

---

## Where to start

| If you want to… | Read |
|---|---|
| Install the package | [`getting-started/installation.md`](getting-started/installation.md) |
| Configure languages, prefix, middleware | [`getting-started/configuration.md`](getting-started/configuration.md) |
| Build your first CRUD screen | [`getting-started/first-screen.md`](getting-started/first-screen.md) |
| Understand controllers + auto-routing | [`architecture/controller.md`](architecture/controller.md) |
| Understand the Screen engine | [`architecture/screen-engine.md`](architecture/screen-engine.md) |
| Look up a component | [`components/README.md`](components/README.md) |
| Look up an action | [`actions/README.md`](actions/README.md) |
| See the full PHP API reference | [`api-reference/php-api.md`](api-reference/php-api.md) |

---

## Documentation map

```
docs/
├── README.md                    ← you are here
├── CHANGELOG.md                 # Keep-a-Changelog format
├── CONTRIBUTING.md              # Docs-sync rule (READ THIS)
├── getting-started/
│   ├── installation.md
│   ├── configuration.md
│   └── first-screen.md
├── architecture/
│   ├── controller.md
│   ├── screen-engine.md
│   ├── http-rules.md
│   └── json-envelope.md
├── components/
│   ├── README.md                # Index
│   └── *.md                     # One file per component
├── actions/
│   ├── README.md                # Index
│   └── *.md                     # One file per action
├── auth/
│   └── fortify.md
├── permissions.md
├── translations.md
└── api-reference/
    └── php-api.md
```

---

## Core principles

1. **Backend only.** Host apps never write Vue/TS/CSS per screen. Components, screens, and actions are PHP classes serialized into Inertia page props.
2. **Auto-routing.** Controllers extend [`DarejerController`](architecture/controller.md). REST methods auto-register; custom endpoints declare the `#[Route]` attribute. `routes/web.php` stays empty.
3. **Single Screen page.** Every screen — list, create, edit, show, dialog — renders through one generic `Screen.vue`. The PHP `Screen` builder ships the schema; Vue just paints it.
4. **Strict HTTP rules.** Use `useHttp` (data fetch), `useForm` (form submit), `router` (navigation) from `@inertiajs/vue3`. Never `fetch`, `axios`, or `XMLHttpRequest`. See [`architecture/http-rules.md`](architecture/http-rules.md).
5. **Dialog is a mode, not a screen.** Add `->dialog()` to any screen or action. Same PHP class, same route — different rendering.

---

## Important rule for contributors

**Documentation is part of the code.** Any change to a component, action, or PHP API ships with its doc update in the same commit. See [`CONTRIBUTING.md`](CONTRIBUTING.md).
