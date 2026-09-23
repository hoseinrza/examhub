# Exam Filter System — UI/UX Design Spec

Scope: `Examhub_Widget_Search_Filter` (`elementor/widgets/class-widget-search-filter.php`), its styles (`public/css/examhub-cards.css`) and behavior (`public/js/examhub-frontend.js`). This spec upgrades the existing flat-filter widget to a token-based, fully Elementor-editable system without changing its data model (independent facets, AND/OR combine, AJAX re-query).

---

## 1. Full UI layout

### Desktop (≥1025px)

```
┌──────────────────────────────────────────────────────────────────────┐
│ [مقطع ▾] [پایه ▾] [رشته ▾] [درس ▾] [جستجو…]   |  بازنشانی   اعمال فیلتر │
└──────────────────────────────────────────────────────────────────────┘
[ مقطع: متوسطه دوم ✕ ]  [ رشته: ریاضی ✕ ]
─────────────────────────────────────────────  ← __chips, aria-live
۱۲ آزمون یافت شد                                ← __result-count
[ exam grid ]
[ نمایش بیشتر ]
```

- One row, 48px field height, RTL flow (`flex-direction: row` mirrors automatically under `dir="rtl"`).
- Fields are visually grouped (equal height, shared border style); a 1px divider separates the input group from the action group — `Reset` and `Apply` never float as part of the field row.
- `Apply` is the only filled/primary control in the bar. Everything else (selects, search, Reset) is neutral so the eye has exactly one place to land.
- Featured-majors row, when `examhub_featured_majors_position = above`, renders *above* this whole block; `below` renders it between the chips row and the result count. It is **never** a child of `.examhub-search-filter__panel` — see §7 isolation rule.

### Mobile (≤768px)

```
رشته‌های پیشنهادی
[ ریاضی ●] [ تجربی ] [ انسانی ] [ زبان ]   ← horizontal scroll, sibling of popup
──────────────────────────────────────────
[ 🔽 فیلترها ]                              ← full-width trigger, opens sheet
──────────────────────────────────────────
۱۲ آزمون یافت شد
[ exam grid ]
```

The bar/select markup is not duplicated for mobile — `examhub-frontend.js` repositions the *same* `.examhub-search-filter__panel` DOM node into the bottom sheet via CSS only (current behavior, kept). This is correct and should stay: one source of truth for field state, no desktop/mobile drift.

### Breakpoints (tokens, not hardcoded in CSS)
```
--ex-bp-mobile:  0–767px    → popup mode (hybrid/popup_always)
--ex-bp-tablet:  768–1024px → inline, fields wrap to 2 rows if needed
--ex-bp-desktop: 1025px+    → inline, single row
```

---

## 2. Component breakdown (Figma frame list)

```
📁 Exam Filter System
 ├─ 📁 01 Tokens          Colors / Type / Spacing / Radius / Shadow / Motion
 ├─ 📁 02 Atoms
 │   ├─ Dropdown (default / open / selected / disabled / multiselect)
 │   ├─ Chip — Active Filter (with ✕)
 │   ├─ Chip — Suggested Subject (inactive / active / hover)
 │   ├─ Button — Primary (Apply: default/hover/active/loading/disabled)
 │   ├─ Button — Outline (Reset: default/hover/disabled)
 │   ├─ Button — Filters Trigger (mobile, icon+text, badge-count variant)
 │   ├─ Search Input
 │   └─ Accordion Row (collapsed / expanded)
 ├─ 📁 03 Molecules
 │   ├─ Filter Bar (desktop, inline)
 │   ├─ Active Filters Row (chip list)
 │   ├─ Suggested Subjects Carousel
 │   └─ Accordion Group (4 sections: Level/Grade/Major/Subject)
 ├─ 📁 04 Organisms
 │   ├─ Desktop Filter Block  (Bar + Active Filters Row)
 │   ├─ Mobile Filter Trigger Block (Carousel + Button)
 │   └─ Filter Bottom Sheet  (Header + Accordion + Quick Pills + Sticky Footer)
 ├─ 📁 05 States              dropdown/chip/button/popup state matrices (§6)
 ├─ 📁 06 Light / Dark        every organism duplicated, tokens swapped only
 └─ 📁 07 Elementor Mapping   control → element annotation overlay
```

---

## 3. Design tokens

```css
:root {
  /* Color */
  --ex-color-primary:        #F25A1B;
  --ex-color-primary-hover:  #D94E14;
  --ex-color-primary-tint:   #FDEEE6;  /* active chip / selected bg */
  --ex-color-bg:             #F9FAFB;
  --ex-color-surface:        #FFFFFF;
  --ex-color-border:         #E5E7EB;
  --ex-color-text:           #111827;
  --ex-color-text-secondary: #6B7280;
  --ex-color-success:        #10B981;
  --ex-color-error:          #EF4444;

  /* Typography */
  --ex-font-family: 'Inter', 'IRANSans', sans-serif;
  --ex-text-title:   600 18px/1.4 var(--ex-font-family);
  --ex-text-body:     400 14px/1.6 var(--ex-font-family);
  --ex-text-caption:  400 12px/1.4 var(--ex-font-family);

  /* Spacing (8px grid) */
  --ex-space-1: 4px;  --ex-space-2: 8px;  --ex-space-3: 12px;
  --ex-space-4: 16px; --ex-space-5: 24px; --ex-space-6: 32px;

  /* Radius */
  --ex-radius-button: 12px;
  --ex-radius-card:   16px;
  --ex-radius-chip:   999px;
  --ex-radius-field:  12px;

  /* Elevation (popup/dropdown only — never on the bar or cards) */
  --ex-shadow-popup:    0 -4px 18px rgba(0,0,0,.12);
  --ex-shadow-dropdown: 0 4px 12px rgba(0,0,0,.08);

  /* Motion */
  --ex-ease-standard: cubic-bezier(.4,0,.2,1);
  --ex-duration-fast: 150ms;
  --ex-duration-sheet: 250ms;

  /* Sizing */
  --ex-field-height: 48px;
}
```

Dark mode overrides **only** these (matches `examhub-dark-mode.css` convention of scoping to `[data-theme="dark"]` / `prefers-color-scheme`):

```css
[data-theme="dark"] {
  --ex-color-bg:             #0B0F14;
  --ex-color-surface:        #151A21;
  --ex-color-border:         #262D38;
  --ex-color-text:           #F3F4F6;
  --ex-color-text-secondary: #9AA3AF;
  --ex-color-primary-tint:   rgba(242,90,27,.16);
  --ex-shadow-popup:    0 -4px 18px rgba(0,0,0,.5);
  --ex-shadow-dropdown: 0 4px 12px rgba(0,0,0,.4);
}
```
`--ex-color-primary` and `--ex-color-error/success` stay identical in both modes — brand and semantic colors don't shift with theme, only surfaces/text/borders do (enforces §7's "dark mode must only touch background/text/border" rule mechanically).

Map these onto the existing Elementor `--examhub-sf-*` CSS custom properties the widget already emits (`--examhub-sf-field-bg`, `--examhub-sf-apply-bg`, `--examhub-sf-panel-bg`, etc.) — those stay as the *per-instance override* layer; the `--ex-*` tokens above are the *theme default* layer that the per-instance vars fall back to.

---

## 4. Interaction behavior map

| Trigger | Component | Behavior |
|---|---|---|
| Click a desktop `<select>` | Dropdown | Opens native/custom list; border → `--ex-color-primary`; on selection, field gets `.has-value` (border + tint, matches current `examhub-sf-active-color` var) |
| Change any field | Field | **No query fires.** Value is staged only — matches current "Apply-gated" model (`description` text on `examhub_apply_text` control already states this) |
| Click "اعمال فیلتر" | Apply button | → `.is-applying` (spinner shown, label dims, button disabled) → AJAX resolves → chips row repaints → `.is-applying` removed |
| Click "حذف فیلترها" | Reset button | Clears all staged + applied values, removes all chips, re-queries with empty filters immediately (Reset is a direct action, not staged — no second Apply needed) |
| Click chip ✕ | Active filter chip | Removes that one value, re-applies immediately (same as Reset semantics — direct, not staged) |
| Tap "فیلترها" (mobile) | Filters trigger | Repositions `.examhub-search-filter__panel` into fixed bottom-sheet position; `slide-up` (translateY 100%→0) or `fade` per `examhub_popup_animation`; overlay fades in; trigger gets `aria-expanded="true"`; body scroll locked |
| Tap overlay / close icon / Esc | Bottom sheet | Reverse animation; staged-but-unapplied changes are **kept** (closing ≠ canceling — only Reset/✕ clear values) |
| Tap accordion row header | Accordion section | Expands that section, current chevron rotates 180°; other sections may stay open (independent accordion, not single-open) since facets are independent in the data model |
| Tap a suggested-subject chip | Suggested Subjects | Sets the `field` (رشته) facet's staged value and marks the chip `.is-active` — same staged model as every other field, user still taps Apply. It only *looks* independent because it lives outside the popup (Popup Isolation, §7), not because it bypasses the Apply gate |
| Apply button while a request is in-flight | Apply button | Disabled, no double-submit; second click ignored until response lands |
| No results | Result area | Empty-state message + "حذف فیلترها" inline shortcut, not a dead grid |

---

## 5. Elementor control mapping

Direct extension of the controls already in `class-widget-search-filter.php`:

| UI element | Existing control | New/changed control needed |
|---|---|---|
| Each filter field (Level/Grade/Major/Subject/Year/Term/Type) | `examhub_show_{key}` switch, `examhub_multiselect_{key}` switch | — (sufficient) |
| Field label/placeholder | `examhub_label_{key}` TEXT control (`get_filter_label()` falls back to `get_field_label()`) | — (implemented) |
| Suggested Subjects | `examhub_show_featured_majors`, 4× `examhub_featured_major_{i}` SELECT2 | **add** `examhub_featured_majors_max` NUMBER (currently hardcoded to 4 slots) for a true repeater feel, or migrate the 4 fixed slots to a Elementor `REPEATER` control |
| Apply / Reset text | `examhub_apply_text`, `examhub_reset_text` | — (sufficient); **add** icon controls (`ICONS` type) to match spec's "text + icon control" requirement |
| Popup layout type | `examhub_layout_mode` (`hybrid` / `inline_always` / `popup_always`) | — (already covers "modal vs bottom sheet vs inline" via `examhub_popup_animation`) |
| Popup animation | `examhub_popup_animation` (`slide-up` / `fade`) | — (sufficient) |
| Bar spacing | `examhub_bar_gap` (responsive SLIDER) | — (sufficient) |
| Field radius/colors | `examhub_field_radius`, `examhub_field_bg/color/border(+focus)` | — (sufficient) |
| Apply/Reset style | `examhub_submit_*`, `examhub_reset_*` (normal/hover tabs) | **add** disabled-state color tab to each, currently only normal/hover/focus exist |
| Popup colors | `examhub_overlay_color`, `examhub_panel_bg`, `examhub_active_filter_color` | — (sufficient) |
| Visibility per breakpoint | none today beyond `examhub_layout_mode` | **add** native Elementor responsive visibility classes on the wrapper, or a `HIDDEN_DEVICE` control set so admins can hide individual fields per breakpoint, not just switch overall layout mode |
| Accordion in popup | `examhub_popup_field_style` SELECT: `flat` / `accordion` — implemented; mobile-only, each section toggles independently, multi-select fields show a "+N" badge on the collapsed header | — (implemented) |

---

## 6. UX improvements vs. the current implementation

Findings from reading `class-widget-search-filter.php` + `examhub-cards.css` directly:

1. **Native `<select>` everywhere.** Current fields are plain HTML selects — no custom dropdown chrome, so desktop and mobile render browser-default popovers that can't carry the brand's radius/shadow/focus tokens. *Fix:* skin via a custom listbox (still backed by a real `<select>` for a11y/forms-fallback, but visually replaced) so `--ex-radius-field`, `--ex-shadow-dropdown`, and the open/selected states in §6 of this spec are actually visible.
2. ~~**Popup body is a flat list, not an accordion.**~~ **Fixed** — `examhub_popup_field_style = accordion` wraps each field in a collapsible `.examhub-search-filter__field-header`/`-body` pair on mobile (flat fields still get a divider for visual grouping even when accordion is off).
3. ~~**No multi-select chip feedback inside the field itself.**~~ **Fixed** — `.examhub-search-filter__field-badge` shows a live selection count next to the accordion header, updated on `change`, on chip-removal, and on initial page load (`seedFromDefaults`).
4. **Active-filter chips and result count are visually adjacent with no separation rule**, risking the "no mixed UI patterns" principle when an admin also enables chips + many active filters at once on narrow viewports. *Fix:* the chips row gets its own scroll container (`overflow-x:auto` with edge fade) instead of free-wrapping into multiple lines next to the result count.
5. **Suggested Majors correctly sit outside the popup already** (`render()` explicitly comments on Popup Isolation, lines 797–813) — this is a strength to *preserve*, not a gap. Confirms the architecture already agrees with Task 4's requirement; just needs the chip *style* (filled-tint active state) formalized as a token rather than ad hoc `is-active` color.
6. ~~**No loading/disabled states defined for the field selects themselves.**~~ **Fixed** — `lockFilterUI()`/`unlockFilterUI()` now disable every `__select`, `__search`, and `__featured-major` control for the duration of the AJAX request, not just Apply/Reset, so the existing `__select:disabled` CSS is no longer dead code.
7. **Dark mode coverage is doc-tracked but not token-scoped** — `DARK-MODE-SELECTOR-INDEX.md` suggests per-selector overrides rather than a small set of CSS variables. *Fix:* adopt the token list in §3 so dark mode is "swap 6 variables," not "audit every selector," which is exactly the Task 7 requirement and reduces future regressions when new fields are added.

---

## 7. Consistency rules (prevent future bugs)

1. **Popup Isolation** — Suggested Subjects / Featured Majors must always be a DOM sibling of `.examhub-search-filter__panel`, never a child. (Already enforced in code; keep the existing doc-comment as a guard for future edits.)
2. **Single source of truth for fields** — the filter bar markup is rendered once and *repositioned* by CSS/JS for mobile, never duplicated. Any new field type must go through the same `FIELDS` map + `render()` loop, not a parallel mobile-only block.
3. **Apply is staged, Reset/✕/chip-removal are immediate** — never let a future field type skip the Apply gate (e.g. don't auto-query on every `<select>` change) and never make Reset require a second Apply click. Mixing these two models on the same bar is the "no mixed UI patterns" violation to avoid.
4. **One primary action per screen** — only the Apply button may use `--ex-color-primary` as a *fill*. Suggested-subject active chips use the *tint* (`--ex-color-primary-tint` bg + primary text/border), not the solid fill, so they never compete visually with Apply.
5. **Dark mode touches tokens, not selectors** — any new component must be styled exclusively with the `--ex-*` variables in §3. If a selector needs a dark-mode-specific rule that isn't a token swap, that's a sign the component should gain a new token instead of a one-off override.
6. **Breakpoint logic lives in one place** (`examhub_layout_mode` + the three breakpoint tokens in §1) — don't introduce a second, independent mobile-detection mechanism (e.g. JS `innerWidth` checks) that can drift out of sync with the CSS breakpoints.
7. **Every interactive state must exist before shipping a component** — dropdown (default/open/selected/disabled), chip (inactive/active/hover), button (default/hover/active/loading/disabled). A component missing one of these is incomplete, not "fine for now."
