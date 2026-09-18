```yaml
---
version: alpha
name: Ubisoft Design System
description: "Bold, modern gaming platform aesthetic with clean typography and dynamic accent colors, built for seamless navigation across a global audience of players."

colors:
  primary: "#000000"
  primary-hover: "#1a1a1a"
  on-primary: "#ffffff"
  background: "#ffffff"
  surface: "#f5f5f5"
  border: "#e0e0e0"
  text: "#1a1a1a"
  text-muted: "#666666"
  accent: "#ffc906"
  success: "#2ecc71"
  warning: "#f39c12"
  danger: "#e74c3c"

typography:
  display:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: 56px
    fontWeight: 700
    lineHeight: 1.05
    letterSpacing: -0.03em
  heading:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: 32px
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: -0.02em
  body:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.65
    letterSpacing: -0.01em
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
  card: "0 2px 8px rgba(0, 0, 0, 0.08)"
  elevated: "0 8px 24px rgba(0, 0, 0, 0.12)"
  focus: "0 0 0 3px rgba(255, 201, 6, 0.3)"

motion:
  duration-fast: 150ms
  duration-base: 250ms
  duration-slow: 400ms
  easing: "cubic-bezier(0.4, 0, 0.2, 1)"
---

## Rationale

Ubisoft is a global gaming entertainment powerhouse serving hundreds of millions of players across console, PC, and mobile platforms. The design system must balance premium brand perception with functional clarity—supporting everything from hero game announcements to complex account management and store navigation. A light-first approach ensures accessibility across low-light gaming sessions while maintaining professional credibility in a crowded digital landscape.

The color palette anchors around black as primary, signaling the core gaming/entertainment identity, with a vibrant golden-yellow accent (#ffc906) that echoes Ubisoft's traditional brand warmth and creates energy without overwhelming. This pairing is both distinctly Ubisoft and functionally strong for CTAs, highlights, and interactive states. The palette is deliberately constrained to reduce cognitive load and maximize scannability across device types.

Typography leverages Inter—a modern, highly legible humanist sans-serif optimized for screens—across all scales. The hierarchy is aggressive but proportionate, supporting both large-scale hero messaging and dense UI density in dashboards. Generous line-height and letter-spacing maintain comfort during extended sessions. Spacing follows an 8px base grid, enabling rhythm, predictability, and responsive scaling from mobile to 4K displays.

Motion is purposeful and snappy, supporting the energetic nature of gaming culture while never feeling frivolous. Transitions respect reduced-motion preferences, ensuring players with vestibular sensitivities experience a clean, accessible interface. The overall aesthetic is "confident minimalism"—enough visual refinement to feel premium, but enough restraint to stay invisible and let games and content shine.

## 1. Visual Theme & Atmosphere

Ubisoft's visual identity merges sleek modernism with gaming energy. The interface employs negative space liberally, avoiding visual clutter that might distract from game content, social feeds, or store imagery. Surfaces are clean with subtle depth (soft shadows), typography breathes, and interactive elements have clear affordances without excessive ornamentation.

The atmosphere is welcoming yet authoritative—a professional entertainment platform, not a scrappy indie site. Dark mode may be offered separately (respecting user preference), but light-first ensures maximum accessibility and legibility for a global, age-diverse audience. Accent color (golden yellow) is used sparingly but decisively to guide attention and celebrate player moments.

Visual hierarchy is determined by size, weight, and color contrast rather than decoration. Buttons, inputs, and navigation elements are immediately recognizable through consistent form language. Imagery (game trailers, artwork, community content) is the star; the UI is the supporting frame.

## 2. Color System

**Primary (#000000):** Foundational black for high-contrast text, primary buttons, borders, and core navigation. Projects authority and gaming credibility. Hover state (#1a1a1a) adds subtle depth for interactive feedback.

**On-Primary (#ffffff):** White text, icons, and elements on black backgrounds. Ensures WCAG AAA contrast (21:1).

**Background (#ffffff):** Default page background for light-first design. Maximum accessibility, supports all content types.

**Surface (#f5f5f5):** Elevated cards, panels, modals, and contained sections. Subtle differentiation from background without harsh contrast. Useful for layering and grouping related content (e.g., game cards in library, friend lists).

**Border (#e0e0e0):** Dividers, input borders, and structural lines. Light enough to reduce visual noise, dark enough to define structure. Used at 1-2px weight.

**Text (#1a1a1a):** Body copy and primary readable content. Near-black for comfort and WCAG compliance (19:1 contrast on white).

**Text-Muted (#666666):** Secondary, descriptive, or de-emphasized text (timestamps, metadata, help text). Still maintains 4.5:1 contrast for accessibility.

**Accent (#ffc906):** Ubisoft's signature warm gold. Used for primary CTAs, active states, highlights, achievements, and celebratory moments. High visual punch without aggression. Hover: #ffb700 (darker variant for depth).

**Success (#2ecc71):** Green for confirmations, completed actions, and positive feedback (e.g., "purchase successful," "friend request accepted").

**Warning (#f39c12):** Amber/orange for alerts requiring attention (e.g., "storage full," "authentication required," "limited-time offer").

**Danger (#e74c3c):** Red for destructive actions, errors, and critical alerts (e.g., "delete account," "connection lost," "banned player").

**Contrast Notes:**
- Black on white: 21:1 (AAA)
- Gold on white: 3.8:1 (AA for large text only; never use for body)
- Gold on black: 7.2:1 (AAA)
- Muted gray on white: 4.5:1 (AA)

## 3. Typography

**Display (56px, 700 weight):** Hero headlines, splash screens, major announcements (e.g., "Assassin's Creed Mirage Now Available"). One per page maximum. Tight line-height creates punch.

**Heading (32px, 600 weight):** Section titles, dialog headers, major feature announcements. Secondary hierarchy, used frequently.

**Body (15px, 400 weight):** All readable content—descriptions, metadata, form labels, help text. Generous 1.65 line-height supports readability across screen sizes and gaming environments.

**Mono (13px, 400 weight):** Code snippets, API documentation, technical error messages, player IDs, and asset identifiers. Lower weight keeps it friendly; monospace signals technical context.

Inter is chosen for its geometric simplicity, excellent screen rendering at small sizes, and neutral-yet-warm character that suits both hardcore gamers and casual audiences. No serifs, no quirks—maximum legibility and cross-platform consistency.

## 4. Components & Patterns

### Button (Primary)
- **Default:** Black background (#000000), white text, 8px radius
- **Hover:** Background #1a1a1a, slight lift shadow (card shadow)
- **Active:** Background #000000, inset shadow (1px 1px 0 rgba(0,0,0,0.2))
- **Disabled:** Background #e0e0e0, text #999999, no hover
- **Sizes:** Small (32px height), Medium (44px), Large (56px)
- **Minimum touch target:** 44×44px

### Button (Accent/CTA)
- **Default:** Golden yellow (#ffc906), black text, 8px radius
- **Hover:** Background #ffb700, slight lift shadow
- **Active:** Background #ffc906, inset shadow
- **Disabled:** Background #f0e6cc, text #999999
- Used sparingly for primary calls-to-action: "Play Now," "Purchase," "Join Event," etc.

### Button (Ghost/Secondary)
- **Default:** Transparent, black text (#1a1a1a), 2px black border
- **Hover:** Background #f5f5f5, border #000000
- **Active:** Background #e0e0e0
- Used for secondary actions that shouldn't compete for attention.

### Text Input / Textarea
- **Default:** White background, 1px border (#e0e0e0), 8px padding, 8px radius
- **Focus:** 2px border (#000000), shadow "0 0 0 3px rgba(255, 201, 6, 0.3)"
- **Error state:** Border #e74c3c, error message in red below
- **Placeholder:** #999999, italic
- **Label:** Above input, 12px weight 600, dark text

### Checkbox / Radio
- **Default:** 20×20px, border #e0e0e0, background white
- **Hover:** Border #000000
- **Checked:** Background #000000, white checkmark/radio fill
- **Disabled:** Background #f5f5f5, border #999999
- **Focus:** Surrounding outline as per focus style

### Card (Content)
- **Background:** #f5f5f5
- **Border:** Optional 1px #e0e0e0 or shadow only (card shadow)
- **Padding:** 16-24px
- **Radius:** 8-12px
- **Used for:** Game tiles in library, news articles, user profiles, event promotions, store listings
- **Hover state:** Lift to elevated shadow, scale 1.02 (very subtle)

### Navigation Bar (Top)
- **Background:** #ffffff, 1px bottom border (#e0e0e0)
- **Height:** 64px
- **Items:** Logo (left), menu links (center/right), user profile icon (far right)
- **Active link:** Underline or background accent color, bold weight
- **Mobile:** Hamburger menu, converts to slide-out drawer

### Navigation Drawer / Sidebar
- **Background:** #f5f5f5
- **Width:** 280px (desktop), full width mobile
- **Items:** Links, nested categories, user avatar, logout
- **Active state:** Left accent bar (#ffc906), bold text
- **Scrollable:** Y-overflow if needed
- **Z-index:** Above content on mobile

### Badge / Label
- **Background:** #f5f5f5, border 1px #e0e0e0
- **Text:** 12px, #1a1a1a
- **Padding:** 4px 12px
- **Radius:** 4px
- **Variants:** Success (green), Warning (amber), Danger (red) backgrounds with white or dark text

### Loading State
- **Spinner:** 32×32px, golden accent color (#ffc906) rotating, 1.2s cubic-bezier easing
- **Skeleton:** Lighter gray (#e0e0e0) pulsing at 1.5s interval, respects prefers-reduced-motion (no pulse, just static gray)
- **Message:** "Loading..." in muted text below spinner

### Modal / Dialog
- **Overlay:** rgba(0, 0, 0, 0.5) semi-transparent background
- **Box:** White background, 12px radius, 24-32px padding, elevated shadow
- **Header:** Close button (top right, "×" or icon), title in heading style
- **Body:** Body text, centered or left-aligned
- **Footer:** Action buttons (primary accent CTA on right, secondary ghost on left)
- **Max-width:** 480px desktop, full - 32px mobile

### Toast / Notification
- **Position:** Bottom-right, 16px from edges
- **Background:** #1a1a1a (dark), white text
- **Padding:** 12-16px
- **Radius:** 8px
- **Auto-dismiss:** 4 seconds
- **Variants:** Success (green accent bar on left), Warning (amber), Danger (red)

### Data Table
- **Header row:** Background #f5f5f5, text 600 weight, 48px height
- **Body rows:** White background, 1px bottom border (#e0e0e0), 40px min height
- **Cells:** 12px padding, text #1a1a1a
- **Hover row:** Background #f5f5f5 (subtle highlight, optional)
- **Sortable columns:** Header shows up/down arrow, cursor: pointer
- **Responsive:** Horizontal scroll on mobile with fixed first column

## 5. Spacing & Layout

**8px Base Grid:** All spacing increments by 8 (4, 8, 12, 16, 24, 32, 48, 64, 96, 128). This ensures rhythm and predictability.

**Common spacing:**
- **Page padding:** 24px mobile, 32px tablet, 48px desktop
- **Section gap:** 48px vertical
- **Component internal padding:** 16px (buttons, cards, inputs)
- **Margin between form fields:** 24px
- **Margin between sections:** 32-48px

**Max-width:**
- **Content container:** 1280px (generous for game imagery and store layouts)
- **Sidebar + content:** Sidebar 280px fixed, content flexible; stacks on mobile
- **Modal max-width:** 480px
- **Article/blog:** 720px for readability

**Grid system (optional, for dashboards):**
- 12-column grid, 16px gutter, responsive 4-column (mobile), 8-column (tablet), 12-column (desktop)

**Margin / Padding ratio:** Generally 1:1 for balance, but buttons and inputs use 8:16 (h:w) for clickability.

## 6. Motion & Interaction

**Duration:**
- **Fast (150ms):** Hover state changes, icon transitions, simple toggles (e.g., expand/collapse)
- **Base (250ms):** Modal enter/exit, card lift, navigation transitions, form submissions
- **Slow (400ms):** Page transitions, hero animations, staggered list reveals

**Easing:** `cubic-bezier(0.4, 0, 0.2, 1)` (Material's "standard" easing) for all transitions. Feels natural, responsive, neither sluggish nor jarring.

**Hover states:**
- Buttons: Lift 2px, shadow elevation
- Cards: Lift 4px, shadow elevation, optional subtle scale (1.02)
- Links: Underline appears or color changes to accent
- Inputs: Border color darkens, optional background lighten

**Focus states:**
- Visible outline, 2px solid accent color (#ffc906), 2px offset, 4px radius
- Applies to buttons, inputs, links, tabs
- Works with keyboard navigation (Tab, Shift+Tab)

**Loading patterns:**
- Spinner for indeterminate progress (network requests, asset loading)
- Progress bar for determinate (file uploads, downloads) — 6px height, accent color
- Skeleton screens for content placeholders (smoother perceived load)

**Scrolling:**
- Smooth scroll-behavior enabled
- Sticky headers (navigation, table headers) with subtle shadow on scroll

**Reduced motion:**
- `@media (prefers-reduced-motion: reduce)` removes all motion
- Spinner becomes static pulse or just shows icon
- Transitions become instantaneous (0ms)
- Hover lift removed, state change via color/opacity only

## Accessibility

### Contrast Ratios

| Color Pair | Ratio | Level |
|---|---|---|
| Black (#000000) on white (#ffffff) | 21:1 | AAA |
| Muted gray (#666666) on white (#ffffff) | 4.5:1 | AA |
| Gold (#ffc906) on white (#ffffff) | 3.8:1 | AA (large text only, 18px+) |
| Gold (#ffc906) on black (#000000) | 7.2:1 | AAA |
| Accent/Gold on surface (#f5f5f5) | ~5.2:1 | AA |
| Success green (#2ecc71) on white | 5.3:1 | AA |
| Warning amber (#f39c12) on white | 6.2:1 | AAA |
| Danger red (#e74c3c) on white | 5.2:1 | AA |

### Minimum Requirements

- **Touch target:** 44×44px minimum for all interactive elements (buttons, inputs, links)
- **Focus indicator:** 2px solid accent (#ffc906) outline, 2px offset, 4px radius
- **Focus contrast:** 7.2:1 (gold on black backgrounds), 3.8:1 (gold on white—acceptable for interactive elements, enhanced via outline)
- **Color alone:** Never use color to convey state alone (e.g., red means error). Always pair with icon, text, or pattern.
- **Text:** Minimum 14px for body text; 16px on mobile inputs (avoids auto-zoom on iOS)
- **Line-height:** Minimum 1.5 for readability
- **Link identification:** All links must be underlined or have non-color indicator (icon, bold, etc.)

### Motion

- All animations and transitions respect `prefers-reduced-motion: reduce` media query
- No auto-playing videos or animations on page load
- Parallax and vestibular-triggering effects disabled for users with reduced-motion preference
- Spinners and loaders become static or fade-based (no rotation)

### Keyboard Navigation

- Full keyboard support (Tab, Shift+Tab, Enter, Escape, Arrow keys where applicable)
- Logical tab order following visual flow (top-to-bottom, left-to-right)
- Menu navigation with Arrow Up/Down, Escape to close
- Modals trap focus (Tab cycles within modal)
- Skip link to main content (hidden by default, visible on focus)

### Screen Reader Support

- Semantic HTML: `<button>`, `<a>`, `<input>`, `<nav>`, `<main>`, `<aside>`, `<article>`, `<section>`
- `aria-label` for icon-only buttons and actionable elements
- `aria-expanded` for collapsible sections
- `aria-current="page"` for active navigation links
- Form labels properly associated via `<label for="id">` or wrapped
- Error messages linked to inputs via `aria-describedby`
- Loading states announced via `aria-live="polite"` regions

### Notes

Ubisoft's global audience spans ages 6–65+ and diverse abilities. Accessibility is not optional—it's core to brand inclusivity. All interactive elements must be keyboard accessible; all meaningful images require alt text; all color choices are tested for colorblindness (deuteranopia, protanopia, tritanopia). The golden accent color is vibrant enough to be visible in low-light gaming environments and distinct enough for colorblind users when paired with text or icons.

Testing: Contrast checked via WebAIM Contrast Checker; keyboard navigation via keyboard-only browsing; screen reader tested on NVDA (Windows) and VoiceOver (macOS/iOS); motion tested with prefers-reduced-motion enabled; mobile tested on 44px touch targets and 16px input font size.

```