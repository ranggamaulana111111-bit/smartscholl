# Visual Reconstruction Report — Smart School Enterprise

Date: 2026-09-25
Status: COMPLETE (visual layer per MASTER PROMPT V2). Backend unchanged; feature/QA build green.

---

## 1. Inspiration / Language

Target: **premium international school × modern edtech × editorial design × bento composition × sophisticated-motion** — "well-produced campus publication", not a gaming console and not a dense admin database. Three anchors (see `DESIGN.md`):

- **Editorial typography** — Fraunces display serif for headlines/metrics, uppercase kicker eyebrows with hairline rules.
- **Bento composition** — asymmetric card grids; a four-equal-metric-card dashboard is forbidden (rule #4 in `DESIGN.md`).
- **Restrained motion** — one-shot page enter, scroll reveal, ≤3px hovers; everything dies under `prefers-reduced-motion`.

## 2. Requirements (from master prompt)

1. Higher perceived value & step-change from the default Laravel look.
2. Premium + international school identity, appropriate for teachers, admins, parents, students.
3. Convert every default page (welcome, auth, dashboard, list/CRUD, forms) — no leftover Bootstrap-classic styling.
4. Strong consistent color, typography, spacing system.
5. Mobile-first responsive; distinct "editorial premium" feel; **no clone of any product look**.
6. Sophisticated motion implemented with reduced-motion respect.
7. Backend/routes/RBAC/etc. frozen — visual layer only, plus small copy/brand fixes.
8. Gates: all PHPUnit tests green, Pint clean, Vite build clean, browser QA clean (desktop + mobile).

## 3. Design Principles

- **Paper canvas** — warm paper white `#f7f5f0` rests under white cards; reads "of a school, not a warehouse".
- **Ink navy anchor** — `#16345a` replaces pure black for headlines, sidebar, primary buttons, inverse hero. Color of university crests / school blazers; champagne reads AAA on it.
- **Champagne + bronze accent** — muted champagne `#c79a3a` only for CTA fills, small rules, and marks on navy; bronze `#8c5e00` carries every small text on light surfaces (never gold-on-white).
- **One focal hero per page**; asymmetry over rhythm; typographic scale over ornament.
- **Serif = voice, sans = work** — Fraunces for narrative/metrics, Inter for UI density.

## 4. Color Palette (tokens in `resources/css/app.css`)

| Token | Value | Usage |
|---|---|---|
| `--color-background` (paper) | `#f7f5f0` | page base |
| `--color-surface` (sand) | `#efebe2` | alt sections, stat bands |
| `--color-primary` (ink navy) | `#16345a` | buttons, sidebar, headers, inverse |
| `--color-primary-hover` | `#23508a` | hover |
| `--color-on-primary` | `#ffffff` | text on primary |
| `--color-accent` (champagne) | `#c79a3a` | fills/rules/marks on navy |
| `--color-accent-hover` | `#b08626` | accent hover |
| `--color-accent-deep` (bronze) | `#8c5e00` | small text on light |
| `--color-accent-tint` | `#f4ecd6` | active nav tint |
| `--color-text` | `#202a37` | body |
| `--color-text-muted` | `#5a6474` | secondary (5.36:1 on paper) |
| `--color-border` | `#dfd6c1` | hairline |
| `--color-success` | `#16734b` | status |
| `--color-warning` | `#9a6500` | status |
| `--color-danger` | `#b3261e` | status |

Semantic errors/status always pair tint + icon/dot + text, never color alone.

## 5. Typography

- **Display:** Fraunces 500/600/700 — `font-display`; hero `clamp(34px,5vw,58px)` with `line-height:1.08`, `letter-spacing:-0.02em`; display-title `text-3xl/4xl`; serif also drives metric values and bento titles (voice).
- **UI body:** Inter 400/500/600/700; 13–16px; `text-muted` for support copy.
- **Kicker/eyebrow:** 11px uppercase, `0.18em` tracking, hairline gold rule via `.eyebrow::before` — editorial masthead motif.
- **Data/mono:** Fira Code 400 for codes/scans.
- Sourced via `fonts.bunny.net` in `welcome` + `layouts/app`; verified compiled into the production CSS (`app-Co0ilKtY.css`).

## 6. Spacing & Rhythm

- 4px base grid; section padding `py-16 ... lg:py-20/24`; container `max-w-[1280px]` measured for desktop breathing; `px-6` page margins; card `p-6/p-7/p-9`.
- Bento gutters `gap-5`/`gap-6`; metric blocks separated by vertical rhythm `mt-6/7/8`; hairline `border-border` sections to structure scroll.

## 7. Component Inventory

- `brand-mark` — serif monogram tile with gold rule; `brand-mark--sm` for header/welcome.
- Sidebar (navy→white shell): white surface, active item = `accent-tint` bg + 3px gold bar + serif label; badges for EWS counts.
- `site-header` — sticky, hairline, backdrop-blur (the *only* glass element).
- `page-head` — eyebrow + serif title + optional desc/actions (every list page).
- `bento-card` — base card; `.bento-card--inverse` (navy, hero), `.bento-card--surface` (sand), `bento-card--link` (hover lift ≤3px).
- `metric__label` (uppercase 11px) + `metric__value` (serif, large); `metric__value--accent` uses bronze-on-light.
- `stat-tile` — compact sand tiles inside inverse panels.
- Buttons: `btn-primary` (navy), `btn-accent` (champagne + ink text), `btn-ghost`, `btn-danger`; `btn-sm/lg`.
- Inputs, selects, checkboxes with champagne focus ring + dark offset; tables with hairline rows; `x-empty` empty states; toasts (`aria-live`); mobile drawer.
- Status dots/pills (success/warning/danger) — functional, real, with text.

## 8. Interaction & Motion

- Durations 150/250/420ms; editorial ease-out `cubic-bezier(.22,1,.36,1)` for 420ms.
- Page enter: single 12px fade/slide on `#main-content`.
- Scroll reveal: IntersectionObserver watch on `.reveal`, one-shot, disabled + forces visible under `prefers-reduced-motion` (also guarded inline in `welcome`).
- Hover: card lift ≤3px + border; no looping, no parallax, no tilt/spin (3D "gaming" removed entirely).
- Dials (declared in `DESIGN.md`): **ENERGY 2 · RHYTHM 3 · MOTION 2**.

## 9. Layout Systems

- App shell: fixed white sidebar + `#main-content`; mobile → hamburger + overlay drawer.
- Welcome: full-bleed hero split 50/50 (copy left / product-snapshot inverse bento right), stats band (3 sand cards), 2×2 feature grid, inverse CTA.
- Dashboards: asymmetric bento — hero inverse (branding + `admin` daily pulse), `lg:grid-cols-12` splits 8/4, 7/5, 2/2/2/2/4 etc. Guru dashboard: 4 bento (today attendance, journals 7/5, homeroom 7/5, top alerts). Equal 4-up is prohibited.
- List pages: `page-head` + toolbar + card/table; attendance/EWS have bento summary rows.

## 10. Page-Level Coverage

- `welcome` — rewritten editorial landing (hero, feature modules, stats band, CTA, footer; DB-free, derives brand from `config('app.name')`).
- `auth/login` — split layout: ink-navy brand panel (copyright block) + paper form card.
- `dashboard*` — generic + `admin`, `guru`, `siswa`, `orang-tua` role variants, all hero+bento.
- Tables/forms/detail (`students`, `assessments`, `assignments`, `attendance/*`, `ews`, `journal`, `schedules`, `rombels`, `teachers`, `blog`…): restyled via `page-head`, cards, tables, inputs.
- `parent/dashboard` — bento averages + inverse "Rata-rata" card; `attendance/index` & `ews/index` — status bento blocks.
- `audit-logs` — neutral card list with bronze-accented change tags; `settings` — tab system with navy+gold active state.
- `students/rapor` — standalone print sheet; title separator fixed (no em dash).

## 11. Hover & Focus States

- Focus: 2px bronze outline + 2px dark offset; inputs use champagne ring (`--shadow-focus`).
- Hover: primary → `#23508a`; accent → `#b08626`; links/cards lift ≤3px.
- Active nav: `accent-tint` + 3px gold bar; `aria-current` reflected.

## 12. Accessibility & Responsiveness

- Contrast audited: muted-on-paper 5.36:1; muted-on-sand 4.89:1; white/60-on-navy ≈4.8:1; accent-deep-on-paper >4.5:1; champagne as text **only on navy**.
- Keyboard: full tab order, skip link, Focus-drawer/modal, Escape closes; `aria-expanded`, `aria-label`, `aria-live` toasts, `aria-hidden` decorative snapshot, `aria-current` nav.
- Touch: 44×44mm min targets; 16px inputs on mobile; mobile QA 390px: 0 horizontal overflow, burger works, sidebar hidden.
- `prefers-reduced-motion` kills all animation.

## 13. Implementation Summary

- `resources/css/app.css` — rewritten design system (tokens, base, components, motion; removed all 3D classes).
- `DESIGN.md` — v2.0 identity doc + non-negotiable rules + dials (source of truth).
- Views: `welcome`, `auth/login`, `layouts/app`, `dashboard*`, `components/page-head`/`empty`, `attendance/index`, `ews/index`, `parent/dashboard`, `assessments/show`, `audit-logs/index`, `students/progress` & `rapor`, `attendance/qrcodes`, `guru` col-span fix.
- Copy/brand: `APP_NAME` was the Laravel default → set to `Smart School` so every brand mark/title reads correctly ("S", "Smart School Enterprise") instead of "Laravel Enterprise". No backend logic touched.
- Antislop: `anti-slop/audit-001/002` — 002 filed after reconstruction; all 5 findings approved & fixed (fabricated welcome stats → real feature copy; em dash in rapor title; dials declared in `DESIGN.md`; uniform-card & radius rationale documented).

## 14. QA Verification

- **PHPUnit:** `php artisan test` → **208 passed (556 assertions)**.
- **Pint:** `pint --test` → passed.
- **Build:** `npm run build` → passed (`app-Co0ilKtY.css` 69.08 kB, `app-TEGu6iwb.js` 52.96 kB), Fraunces confirmed compiled.
- **Browser (desktop 1440):** welcome (brand mark "S", hero copy, 0 overflow, 0 console errors), login (navy panel), dashboards admin/guru/siswa/parent (bento counts, serif metrics, hero inverse, guru 7/5 col-span verified via DOM), students/attendance/ews/parent-portal — 0 console errors; screenshot evidence captured during runs.
- **Browser (mobile 390):** welcome/login/dashboard 0 horizontal overflow; hamburger 44px visible; sidebar hidden; 0 console errors.
- Note: `qa-visual.mjs`, `qa-mobile.mjs` and `shots/*.png` were untracked artifacts and were cleaned from disk after the session; re-run those scripts (or the smoke harness) to regenerate screenshot evidence.

## 15. Key Design Decisions (rationale)

1. **Navy ink over black** — university-crest connotation; anchors humanity over UI-framework default.
2. **Paper + sand neutrals** — warmth, "premium print" feel that white-on-white cannot deliver.
3. **Champagne restrained** — accent as a *molecule*, not a wash; bronze for light-bg text prevents the classic gold-on-white contrast failure.
4. **Fraunces serif** — editorial masthead voice; differentiates from every default sans admin.
5. **Bento asymmetry by policy** — hierarchy is operational; equal grids allowed only for equal-weight content (documented).
6. **Removed 3D/gaming motifs** per approved direction — the earlier cube/tilt identity was superseded by the international-school identity; `prefers-reduced-motion` respected.

## 16. Known Limitations / Follow-up

- `shots/` and root QA scripts were cleaned externally (untracked); regenerate with `qa-visual.mjs`/`qa-mobile.mjs` when needed.
- Welcome page intentionally DB-free (no `setting()` calls) — machines the `ExampleTest` under in-memory SQLite; the product snapshot is illustrative and now shows only real feature copy (no fabricated statistics).
- If a production tenant wants its own school name, it must set `APP_NAME`; brand mark/title derive from it.
- No migrations, models, controllers, routes, RBAC, or validation changed for the visual layer. The only code edits were the pre-existing `teacher_id` refactor consistency fixes in tests/factories (needed to restore green) — no behavior change.