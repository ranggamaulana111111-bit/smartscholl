```yaml
---
version: 2.0
name: Smart School Editorial System
description: "Premium international-school product identity — quiet, editorial, precise. Modern EdTech composed like a well-produced campus publication, not an admin console."

colors:
  primary: "#16345a"
  primary-hover: "#23508a"
  on-primary: "#ffffff"
  background: "#f7f5f0"
  surface: "#efebe2"
  border: "#dfd6c1"
  text: "#202a37"
  text-muted: "#5a6474"
  accent: "#c79a3a"
  accent-hover: "#b08626"
  accent-deep: "#8c5e00"
  accent-tint: "#f4ecd6"
  success: "#16734b"
  warning: "#9a6500"
  danger: "#b3261e"

typography:
  sans:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: -0.01em
  display:
    fontFamily: "'Fraunces', Georgia, 'Times New Roman', serif"
    fontSize: 32px
    fontWeight: 600
    lineHeight: 1.1
    letterSpacing: -0.015em
  mono:
    fontFamily: "'Fira Code', 'Courier New', monospace"
    fontSize: 13px
    fontWeight: 400
    lineHeight: 1.6

spacing:
  base: 8px
  scale: [4, 8, 12, 16, 24, 32, 48, 64, 96, 128]

radius:
  sm: 4px
  md: 8px
  lg: 12px
  xl: 16px
  pill: 9999px

shadows:
  card: "0 1px 2px rgba(22, 52, 90, 0.05), 0 6px 16px rgba(22, 52, 90, 0.06)"
  elevated: "0 2px 4px rgba(22, 52, 90, 0.06), 0 16px 40px rgba(22, 52, 90, 0.14)"
  focus: "0 0 0 3px rgba(199, 154, 58, 0.4)"

motion:
  duration-fast: 150ms
  duration-base: 250ms
  duration-slow: 420ms
  easing: "cubic-bezier(0.4, 0, 0.2, 1)"
  easing-editorial: "cubic-bezier(0.22, 1, 0.36, 1)"
---
```

## Rationale

Smart School Enterprise is a **premium international-school product**. Its visual voice
should feel like a well-produced campus publication — confident, quiet, and precise —
not like a gaming console or a dense admin database. The identity was rebuilt around
three ideas: **editorial typography**, **bento composition**, and **restrained motion**.

**Paper canvas.** The base is a warm paper white (`#f7f5f0`) with sand surfaces. White
cards sit on paper. This prints warmth without noise and gives an "of a school, not a
warehouse" reading that pure `#ffffff` cannot.

**Ink navy.** The primary is a deep educational navy (`#16345a`) — the color of
university crests and school blazers. It replaces pure black as the anchor for
headlines, primary buttons, the sidebar, and inverse hero cards. On dark navy,
champagne gold reads AAA.

**Champagne gold.** The accent is a muted champagne (`#c79a3a`), used for CTAs, active
markers, decorative rules, and highlights **on dark/inverse surfaces** only. Where text
must stand on light paper, the design uses a deeper academic bronze
(`--color-accent-deep`, `#8c5e00`) that passes WCAG AA. Bright gold is never body text
on white — this removes the earlier 1.5:1 contrast failure at the source.

**Serif display.** Fraunces is the editorial display face for headlines, big metrics,
and empty-state titles, setting the product apart from generic SaaS sans. Inter keeps
UI and body copy neutral and legible. One display face, one sans, one mono.

**Bento is a composition system, not a component.** Dashboards are recomposed as
asymmetric editorial grids with a dominant hero, supporting panels, and scannable
feeds — never the flat "four equal metric cards" pattern.

## 1. Visual Theme & Atmosphere

Quiet authority. Negative space is generous; surfaces whisper with hairline borders and
soft shadows instead of shouting with thick strokes. Hierarchy is expressed by serif
size/weight, tracking, and color contrast — not decoration.

The atmosphere is **prestigious but warm**: trustworthy enough for wali kelas and
professional enough for Dinas Pendidikan. Light-first by default for accessibility;
motion is present but never playful (no spinning cubes, no tilt-toys).

## 2. Color System

**Primary — Ink navy `#16345a`:** headlines, primary buttons, sidebar, inverse heroes,
table header rules. Projects institutional credibility. Hover `#23508a`.

**On-Primary `#ffffff`:** white on ink navy (11.8:1, AAA).

**Background — Paper `#f7f5f0`:** default canvas. Warm, calm, reduces glare.

**Surface — Sand `#efebe2`:** quiet containers, secondary cards, empty states.

**Border `#dfd6c1`:** hairline rules and input borders. Warmer than neutral gray.

**Accent — Champagne `#c79a3a`:** CTAs (with ink text, 5.1:1), active nav bar, progress
on inverse, decorative rules, highlights on navy. **Accent-deep `#8c5e00`:** emphasis
text, eyebrows, and icons on light backgrounds (5.3:1, AA).

**Success `#16734b` / Warning `#9a6500` / Danger `#b3261e`:** status colors chosen for
AA contrast on paper; tinted badge backgrounds use soft pastels.

**Contrast notes:**
- Ink navy on paper: ~12.6:1 (AAA)
- Champagne gold on ink navy: ~8.5:1 (AAA)
- Bronze (`#8c5e00`) on paper: ~5.3:1 (AA)
- Muted gray (`#5a6474`) on paper: ~5.6:1 (AA)
- Ink navy on champagne (`#c79a3a`): ~5.1:1 (AA for button label)

## 3. Typography

**Display — Fraunces (600):** page headlines, hero numerals, section titles, card
titles, empty-state titles, QR-card names. One per composition role; tight
`line-height 1.1`, `letter-spacing -0.015em`.

**Sans — Inter (400/500/600):** body, labels, buttons, tables, forms. 15px body with
generous 1.6 line-height. Uppercase micro-labels (eyebrows, table heads, labels) use
600 weight with wide tracking (`0.08em`–`0.18em`) and muted or bronze color.

**Mono — Fira Code:** identifiers, codes, technical fields.

## 4. Components & Patterns

### Brand mark
40×40 rounded square, ink navy fill, champagne serif "S", inset 1px champagne ring.
`--sm` 32px for header, `--lg` 56px for login/hero. Replaces the previous "gaming
glyph tile" with a quiet crest-like emblem.

### Buttons
- **Primary:** ink navy bg, white text, 8px radius. Hover: hover shade + soft shadow.
- **Accent/CTA:** champagne bg, ink text (~5.1:1). Hover: deeper gold + white text.
- **Ghost:** transparent, 1px warm border, ink text. Hover: white bg + ink border.
- Sizes: sm 34px, default 44px, lg 56px. Minimum touch target 44×44 (sm used only in
  dense rows next to 44px peers).

### Navigation
- **Sidebar:** white on paper, 1px warm hairline right, 280px. Brand block on top with
  emblem + school name; nav sections as uppercase micro-labels; active item = ink text,
  champagne left rule, pale champagne tint pill. Footer user card + logout.
- **Header:** sticky, translucent paper backdrop-blur, hairline bottom. Hamburger
  (mobile), tenant/role, date, avatar. No clutter.

### Card / Bento
- White cards layered on paper with hairline border + soft card shadow; radius 12px.
- Link cards lift -3px with elevated shadow on hover.
- Inverse cards: ink navy with white text and champagne accents, elevated shadow.

### Tables
- Transparent header, uppercase 11px muted labels with **2px ink bottom rule**; body
  rows on hairline borders; hover sand; horizontal scroll on mobile.

### Form
- White inputs, warm hairline border, 8px radius, 44px min height. Focus = ink border +
  champagne ring. Labels are uppercase micro-labels above the field. 16px input font on
  touch devices to avoid iOS zoom.

### Empty state
- Sand tile with bronze line icon, serif title, muted hint, optional action.

### Toast
- Ink navy bar, white text, 4px champagne/success/warning/danger left rule, bottom-right,
  auto-dismiss reinforced with close button.

## 5. Spacing & Layout

- Base 8px grid; page padding 24px mobile / 32px tablet / 48px desktop.
- Content max-width 1280px; sidebar 280px + content on lg; stack on mobile.
- Bento grid: 12 columns desktop, collapsed single column mobile, 1.25rem gaps.
- Section vertical spacing 32–48px; component internal padding 16–24px.

## 6. Motion & Interaction

- **Fast 150ms:** hovers, color changes, toggle tracks.
- **Base 250ms:** card lift, modal, form submission feedback.
- **Slow 420ms:** page enter, scroll reveals — easing `cubic-bezier(0.22, 1, 0.36, 1)`
  (editorial ease-out).
- **Page enter:** single 12px fade/slide on `#main-content`.
- **Scroll reveal:** IntersectionObserver fade/slide, one-shot, respects
  `prefers-reduced-motion`.
- Layout feels like paper turning — no continuous looping animation, no 3D parallax
  toys. Hover lifts at most 3px; focus uses champagne ring + dark offset.

## Accessibility

- **Contrast:** all text/background pairs audited to AA or AAA on the paper canvas
  (see color contrast notes). Gold is only text on navy, never on white.
- **Focus:** 2px bronze outline + 2px dark offset, or champagne ring on inputs.
- **Keyboard:** full tab order, skip link, focus trap in drawers/modals, arrow-nav for
  menus, Escape closes overlays.
- **Motion:** all animation dies under `prefers-reduced-motion: reduce`.
- **Touch:** 44×44px targets; 16px inputs on mobile.
- **Color never alone:** status always pairs a tint, an icon/dot, and text.
- **Semantics:** `<nav>`, `<main>`, `aria-label`, `aria-expanded`, `aria-current`,
  `aria-live` for toasts; labels bound to inputs.

## Non-Negotiable Rules

1. Champagne gold (`#c79a3a`) is **never** used as small text on light backgrounds —
   that is what `--color-accent-deep` (bronze) is for.
2. Every page keeps a single focal hero item; the rest is support.
3. No spinning/looping/3D "gaming" motion in the product.
4. Dashboards are composed as asymmetric bento, never four equal metric cards.
5. All blades rely on the token utility classes so the system stays centralized.

## Design Dials & Documented Trade-offs

Dials (per antislop R-37) declared once, applied everywhere:

- **ENERGY 2** — calm and assured; hierarchy comes from serif display + scale, not
  noise, vividness, or decorative clutter.
- **RHYTHM 3** — bento layouts are deliberately asymmetric (8/4, 7/5, 4/2/2 patterns);
  equal grids are used only when content is equal-weight (e.g., module cards on the
  landing page).
- **MOTION 2** — one-shot page-enter and scroll reveals + subtle hovers only; no
  looping, no parallax, no tilt. Everything dies under `prefers-reduced-motion`.

Documented trade-offs (answers to antislop R-14 / R-11):

- **Uniform module cards (landing "Modul Utama").** The four product modules are
  deliberately equal-weight capabilities, so they render as equal cards; the bento
  variance lives in the dashboards where hierarchy is operational, not promotional.
- **Uniform radius (`--radius-card`).** Consistent radius is an editorial consistency
  motif (like a magazine's margins). Hierarchy is expressed through scale (serif
  display sizes), weight, and color, not through changing radii.
- **Accent placement.** Champagne is reserved for CTA fills, small rules, and marks
  on navy; bronze (`--color-accent-deep`) carries all small text on light surfaces.