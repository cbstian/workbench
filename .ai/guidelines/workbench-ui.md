# Workbench UI conventions

Read `docs/arquitectura/interfaz.md` before adding a page, a module, a form or a chart. Summary:

## Shell

- Panel pages are Livewire full-page components rendered in `layouts::app` (collapsible Notion-like sidebar + topbar). Never build a new shell per module.
- Sidebar links come from `config/navigation.php` (`groups` / `footer`, optional `can` ability). Register new modules there; do not edit `components/app/sidebar.blade.php`.
- Labelled groups and items collapse (state kept with Alpine `$persist`). A sub-list under an item is a Livewire component in `app/Livewire/Navigation/` set as the item's `children`; refresh it with an event (e.g. dispatch `projects-changed` after saving a project).
- Every page view's root is `<x-ui.page>`. Use the full width by default (lists, boards, dashboards, detail pages); `wide` only for multi-section settings and `narrow` only for a short single form.
- Page anatomy: `<x-ui.page-header>` (title, description, icon, breadcrumbs, `actions` slot) → `<x-ui.section>` blocks → `<x-ui.empty-state>` for empty data.

## Forms

- Fields and rules live in a Livewire Form object (`php artisan livewire:form XxxForm`), persistence in its method; the page component orchestrates and gives feedback with the TallStackUI `Interactions` trait (`$this->toast()->success(...)->send()`).
- Layout: `<x-ui.form-section>` (title + description left, fields right) → `<x-ui.form-grid cols="2">` → TallStackUI fields (`x-input`, `x-password`, `x-select.styled`, `x-textarea`, `x-toggle`…) → `<x-ui.form-actions>` with the submit button last (`loading="method"`).
- Required fields end their label with ` *`. Labels, hints and messages in Spanish; `validationAttributes()` in Spanish.
- Short create/edit forms over a list open in a TallStackUI `<x-slide>`; long forms get their own `narrow` page.

## Styling

- Only semantic tokens (`bg-canvas`, `bg-surface`, `text-ink`, `text-ink-muted`, `border-line`, `bg-hover`…). No raw colors and no `dark:` in application views.
- TallStackUI restyling goes in `App\Providers\TallStackUiServiceProvider` (soft customization), one method per component.

## Charts

- Use `<x-ui.chart :options="$this->someChart" />` (ApexCharts, lazy-loaded, themed from tokens `--wb-chart-1..6`). Build the options array in a `#[Computed]` method of the Livewire class; declare only type, series, labels and axes.
- The ApexCharts skill and the `apexcharts` MCP server document the options. Do not use TallStackUI's `<x-chart>`.
