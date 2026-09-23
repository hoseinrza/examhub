# ExamHub Dark Mode - Color Palette Reference

Quick reference for all CSS variables used in the dark mode implementation.

---

## CSS Variable Quick Reference

### Background Colors
```css
--bg-primary: #1a1a1a;     /* Main background (darkest) */
--bg-secondary: #2d2d2d;   /* Secondary containers, sidebar */
--bg-tertiary: #3a3a3a;    /* Tertiary containers */
```

### Text Colors
```css
--text-primary: #e8e8e8;    /* Main text (highest contrast) */
--text-secondary: #b0b0b0;  /* Secondary text, metadata */
--text-tertiary: #888888;   /* Tertiary text, hints */
--text-muted: #707070;      /* Muted text (use sparingly) */
```

### Borders & Dividers
```css
--border-color: #404040;    /* Standard borders */
--border-light: #333333;    /* Subtle borders */
```

### Interactive & Accent
```css
--accent-color: #3b82f6;    /* Primary action, active state */
--accent-hover: #2563eb;    /* Hover state (darker) */
--accent-light: #60a5fa;    /* Secondary interactive */
```

### Component Backgrounds
```css
--card-bg: #252525;         /* Card backgrounds */
--card-border: #383838;     /* Card borders */
--sidebar-bg: #282828;      /* Sidebar backgrounds */
--input-bg: #1e1e1e;        /* Form input backgrounds */
--input-border: #383838;    /* Form input borders */
```

### Shadow System
```css
--shadow-color: rgba(0, 0, 0, 0.5);
--shadow-sm: 0 1px 3px var(--shadow-color);
--shadow-md: 0 4px 8px var(--shadow-color);
--shadow-lg: 0 8px 16px var(--shadow-color);
--shadow-xl: 0 16px 32px var(--shadow-color);
```

### Status Colors

#### Success
```css
--success-bg: #065f46;      /* Green dark background */
--success-text: #86efac;    /* Green light text */
--success-border: #047857;  /* Green medium border */
```

#### Warning
```css
--warning-bg: #78350f;      /* Amber dark background */
--warning-text: #fcd34d;    /* Amber light text */
--warning-border: #d97706;  /* Amber medium border */
```

#### Error
```css
--error-bg: #7f1d1d;        /* Red dark background */
--error-text: #fca5a5;      /* Red light text */
--error-border: #dc2626;    /* Red medium border */
```

#### Info
```css
--info-bg: #0c2340;         /* Blue dark background */
--info-text: #93c5fd;       /* Blue light text */
--info-border: #1e40af;     /* Blue medium border */
```

### Badge & Highlight
```css
--badge-bg: #d97706;        /* Badge background (amber) */
--badge-text: #ffffff;      /* Badge text (white) */
```

### Tab Styling
```css
--tab-inactive-bg: #383838;    /* Inactive tab background */
--tab-inactive-text: #b0b0b0;  /* Inactive tab text */
--tab-active-bg: #3b82f6;      /* Active tab background */
--tab-active-text: #ffffff;    /* Active tab text */
--tab-border-active: #60a5fa;  /* Active tab border */
```

### Button Styling
```css
--btn-primary-bg: #3b82f6;      /* Primary button background */
--btn-primary-text: #ffffff;    /* Primary button text */
--btn-primary-hover: #2563eb;   /* Primary button hover */
--btn-success-bg: #059669;      /* Success button background */
--btn-success-hover: #047857;   /* Success button hover */
--btn-secondary-bg: #383838;    /* Secondary button background */
--btn-secondary-text: #e8e8e8;  /* Secondary button text */
```

### Utility
```css
--hover-overlay: rgba(255, 255, 255, 0.05);  /* Hover overlay */
--focus-outline: 2px solid #3b82f6;          /* Focus outline */
```

---

## Color Values by Usage Category

### When to Use Each Background Color

| Variable | Purpose | Example |
|----------|---------|---------|
| `--bg-primary` | Page background, main container | Body background |
| `--bg-secondary` | Secondary containers | Sidebar, section backgrounds |
| `--bg-tertiary` | Tertiary containers | Nested containers |
| `--card-bg` | Card containers | Exam cards, content boxes |
| `--sidebar-bg` | Sidebar/panel backgrounds | Filter sidebar, library sidebar |
| `--input-bg` | Form inputs | Text fields, textareas |

### When to Use Each Text Color

| Variable | Purpose | Contrast | Example |
|----------|---------|----------|---------|
| `--text-primary` | Main body text, headings | 15:1 | Page content, titles |
| `--text-secondary` | Metadata, secondary info | 9:1 | Card meta, labels |
| `--text-tertiary` | Hints, helper text | 4.5:1 | Placeholders, hints |
| `--text-muted` | Very faint text (minimal) | ~3:1 | Use sparingly, disabled states |

### Color Contrast Verification

✅ **WCAG AA Compliant Combinations:**
- `--text-primary` (#e8e8e8) on `--bg-primary` (#1a1a1a) = 15.1:1 ✓✓✓
- `--text-secondary` (#b0b0b0) on `--bg-primary` (#1a1a1a) = 9.2:1 ✓✓
- `--text-primary` (#e8e8e8) on `--bg-secondary` (#2d2d2d) = 13.7:1 ✓✓✓
- `--text-secondary` (#b0b0b0) on `--bg-secondary` (#2d2d2d) = 8.3:1 ✓✓
- `--text-primary` (#e8e8e8) on `--card-bg` (#252525) = 14.3:1 ✓✓✓

---

## Implementation Patterns

### Using Variables in CSS

**Pattern:** Always scope to `body.page-id-2918.dark-mode`

```css
body.page-id-2918.dark-mode .my-component {
	background-color: var(--bg-secondary);
	color: var(--text-primary);
	border-color: var(--border-color);
}

body.page-id-2918.dark-mode .my-component:hover {
	background-color: var(--bg-tertiary);
	box-shadow: var(--shadow-md);
}
```

### No Hardcoded Colors
❌ **WRONG:**
```css
body.page-id-2918.dark-mode .component {
	background-color: #252525;  /* Hardcoded! */
	color: #e8e8e8;             /* Hardcoded! */
}
```

✅ **CORRECT:**
```css
body.page-id-2918.dark-mode .component {
	background-color: var(--card-bg);    /* Uses variable */
	color: var(--text-primary);          /* Uses variable */
}
```

---

## Color Palette Hex Values (Quick Copy-Paste)

### Dark Grays
```
#1a1a1a  --bg-primary
#1e1e1e  --input-bg
#252525  --card-bg
#282828  --sidebar-bg
#2d2d2d  --bg-secondary
#3a3a3a  --bg-tertiary
#383838  --border-color, --tab-inactive-bg, --card-border, --input-border
#404040  --border-color
```

### Light Grays (Text)
```
#e8e8e8  --text-primary
#b0b0b0  --text-secondary, --tab-inactive-text
#888888  --text-tertiary
#707070  --text-muted
```

### Blues (Accent)
```
#3b82f6  --accent-color, --btn-primary-bg, --tab-active-bg
#2563eb  --accent-hover, --btn-primary-hover
#60a5fa  --accent-light, --tab-border-active
#93c5fd  --info-text
#0c2340  --info-bg
#1e40af  --info-border
```

### Greens (Success)
```
#065f46  --success-bg
#047857  --success-border, --btn-success-hover
#86efac  --success-text
#059669  --btn-success-bg
```

### Ambers (Warning/Badge)
```
#78350f  --warning-bg
#d97706  --warning-border, --badge-bg
#fcd34d  --warning-text
```

### Reds (Error)
```
#7f1d1d  --error-bg
#dc2626  --error-border
#fca5a5  --error-text
```

### Whites & Neutrals
```
#ffffff  --badge-text, --btn-primary-text, --tab-active-text
```

---

## HSL Values (Alternative Format)

If you prefer HSL color format:

```css
--bg-primary: hsl(0, 0%, 10%);        /* #1a1a1a */
--text-primary: hsl(0, 0%, 91%);      /* #e8e8e8 */
--accent-color: hsl(217, 91%, 60%);   /* #3b82f6 */
--card-bg: hsl(0, 0%, 15%);           /* #252525 */
--border-color: hsl(0, 0%, 25%);      /* #404040 */
```

---

## Visual Color Reference

### Backgrounds (Darkest to Lightest)
```
█ #1a1a1a  bg-primary (main background)
█ #1e1e1e  input-bg (form inputs)
█ #252525  card-bg (cards)
█ #282828  sidebar-bg (sidebars)
█ #2d2d2d  bg-secondary (containers)
█ #3a3a3a  bg-tertiary (tertiary containers)
█ #383838  border-color (borders)
█ #404040  border-light (subtle borders)
```

### Text (Highest to Lowest Contrast)
```
█ #e8e8e8  text-primary (main text, 15:1 contrast)
█ #b0b0b0  text-secondary (secondary, 9:1 contrast)
█ #888888  text-tertiary (hints, 4.5:1 contrast)
█ #707070  text-muted (very faint, minimal use)
```

### Accent (Primary to Light)
```
█ #2563eb  accent-hover (darkest)
█ #3b82f6  accent-color (primary)
█ #60a5fa  accent-light (lightest)
```

---

## Common Customizations

### Changing Primary Accent Color

To change from blue (#3b82f6) to another color:

1. **In `examhub-dark-mode.css` at root:**
```css
body.page-id-2918.dark-mode {
	--accent-color: #YOUR_HEX_HERE;
	--accent-hover: #YOUR_DARKER_HEX;
	--accent-light: #YOUR_LIGHTER_HEX;
}
```

2. **In `class-examhub-public.php` critical CSS:**
```php
--accent-color: #YOUR_HEX_HERE;
```

**Examples:**
- **Purple:** `#a855f7`, `#9333ea`, `#c084fc`
- **Green:** `#10b981`, `#059669`, `#6ee7b7`
- **Red:** `#ef4444`, `#dc2626`, `#fca5a5`
- **Orange:** `#f97316`, `#ea580c`, `#fb923c`

### Adjusting Darkness Level

To make everything darker or lighter, adjust the neutral grays:

```css
/* For darker theme */
--bg-primary: #0f0f0f;      /* Even darker */
--text-primary: #f5f5f5;    /* Brighter text */

/* For lighter theme */
--bg-primary: #242424;      /* Lighter */
--text-primary: #e0e0e0;    /* Slightly darker text */
```

---

## Testing Your Changes

### Contrast Ratio Tools
1. **WebAIM:** https://webaim.org/resources/contrastchecker/
   - Paste hex values
   - Verify 4.5:1 minimum (AA)

2. **Axe DevTools Browser Extension**
   - Scans page for contrast issues
   - Highlights violations

3. **WAVE Browser Extension**
   - Comprehensive accessibility audit

### Visual Inspection
1. Open page 2918 in browser
2. Open DevTools (F12)
3. Inspect elements to verify correct variables are applied
4. Check that no hardcoded colors override variables

---

## Related Documentation

- **Full Implementation Guide:** See `DARK-MODE-IMPLEMENTATION.md`
- **Code Location:** `examhub/public/css/examhub-dark-mode.css`
- **PHP Enqueue:** `examhub/public/class-examhub-public.php`

---

**Last Updated:** 2026-06-21  
**Version:** 1.0.7
