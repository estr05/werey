---
name: Aero-Tactical Spatial Intelligence
colors:
  surface: '#111318'
  surface-dim: '#111318'
  surface-bright: '#37393e'
  surface-container-lowest: '#0c0e12'
  surface-container-low: '#1a1c20'
  surface-container: '#1e2024'
  surface-container-high: '#282a2e'
  surface-container-highest: '#333539'
  on-surface: '#e2e2e8'
  on-surface-variant: '#b9cacb'
  inverse-surface: '#e2e2e8'
  inverse-on-surface: '#2f3035'
  outline: '#849495'
  outline-variant: '#3b494b'
  surface-tint: '#00dbe9'
  primary: '#dbfcff'
  on-primary: '#00363a'
  primary-container: '#00f0ff'
  on-primary-container: '#006970'
  inverse-primary: '#006970'
  secondary: '#bcc7dd'
  on-secondary: '#263142'
  secondary-container: '#3c475a'
  on-secondary-container: '#aab6cc'
  tertiary: '#f4f5ff'
  on-tertiary: '#2c3039'
  tertiary-container: '#d6d9e4'
  on-tertiary-container: '#5b5f68'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#7df4ff'
  primary-fixed-dim: '#00dbe9'
  on-primary-fixed: '#002022'
  on-primary-fixed-variant: '#004f54'
  secondary-fixed: '#d8e3fa'
  secondary-fixed-dim: '#bcc7dd'
  on-secondary-fixed: '#111c2c'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#dfe2ee'
  tertiary-fixed-dim: '#c3c6d1'
  on-tertiary-fixed: '#181c24'
  on-tertiary-fixed-variant: '#434750'
  background: '#111318'
  on-background: '#e2e2e8'
  surface-variant: '#333539'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  telemetry-data:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-caps:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.08em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 16px
  margin-page: 24px
  panel-padding: 20px
  stack-gap: 8px
---

## Brand & Style

The brand personality is professional, precise, and operationally focused. It is designed for high-stakes telemetry environments where clarity and rapid data digestion are paramount. The design system eschews typical "sci-fi" tropes in favor of a sophisticated, high-fidelity HUD aesthetic that feels grounded in real-world aerospace and logistical operations.

The visual style is a blend of **Glassmorphism** and **Modern Corporate** minimalism. By utilizing highly translucent layers and soft backdrop blurs, the UI feels like a series of floating intelligence overlays rather than rigid, boxed software. This "Spatial Overlay" approach ensures that the primary map or data visualization remains the focal point, while controls and telemetry data exist on a secondary, non-obstructive plane.

## Colors

The palette is anchored by a tactical dark navy and charcoal base to minimize eye strain in low-light operational environments. 

- **Primary (#00F0FF):** A sophisticated Cyan used sparingly for critical data points, active states, and trajectory paths. It is chosen for its high legibility against dark backgrounds.
- **Secondary (#4A5568):** A Slate Blue used for secondary information, muted borders, and inactive UI elements.
- **Surface & Base:** The interface utilizes `#0A0C10` for the deepest background layers and `#141820` for floating panels, enhanced with varying degrees of transparency (15% to 60%) to create depth.
- **Status Indicators:** Standardized semantic colors for status (Green for 'Active', Amber for 'Warning', Red for 'Critical') should be desaturated to maintain the professional aesthetic.

## Typography

This design system employs a dual-font strategy. **Inter** provides high legibility for headings, navigation, and general interface text. **JetBrains Mono** is reserved strictly for telemetry data, coordinates, and timestamps, ensuring that numeric values align vertically for easy comparison across data sets.

Hierarchy is established through weight and uppercase labeling for section headers rather than size alone. This maintains a compact, information-dense environment suitable for professional dashboards.

## Layout & Spacing

The layout follows a **Fluid Grid** model with an emphasis on safe margins to protect the "floating" nature of the overlays. 

- **Overlays:** Content panels should never touch the edge of the screen; they maintain a minimum 24px margin from the viewport edge.
- **Rhythm:** A 4px baseline grid governs all internal component spacing, while 16px gutters separate distinct telemetry modules.
- **Responsiveness:** On mobile, the side panels collapse into a bottom-sheet pattern or an expandable drawer, while the main map view remains the primary interaction layer.

## Elevation & Depth

Hierarchy is achieved through **Glassmorphism** and backdrop blurs rather than traditional shadows. 

- **Level 1 (Deep):** The map or primary visualization layer.
- **Level 2 (Panels):** Floating surfaces with a background blur (12px to 20px) and a low-opacity fill (`#141820` at 60%). Borders should be hair-thin (1px) using the secondary slate color at 20% opacity.
- **Level 3 (Active Elements):** Buttons and active chips utilize a subtle inner glow or a primary-colored hair-line border to denote focus, avoiding heavy drop shadows to maintain the HUD feel.

## Shapes

The shape language is purposefully soft to contrast with the rigid, technical data. 
- **Base Panels:** Use a 24px corner radius (`rounded-xl` equivalent) to emphasize the "capsule" or floating nature of the UI.
- **Internal Components:** Buttons and input fields use a 12px radius to maintain visual harmony with the larger panels while appearing distinct.
- **Data Tags:** Small telemetry tags and labels may use pill-shapes (full rounding) to denote status or categories.

## Components

- **Panels:** Highly translucent containers with `backdrop-filter: blur(16px)`. Use a 1px border with a linear gradient from top-left (slightly brighter) to bottom-right to simulate thin glass edges.
- **Buttons:** Tactical styling. Secondary buttons are transparent with a Slate border. Primary buttons use a solid Slate fill with White or Cyan text. No heavy glows; use a simple opacity shift on hover.
- **Telemetry Chips:** Horizontal layouts containing an icon, a Mono-spaced value, and a small sparkline if applicable.
- **Integrated Overlays:** Avoid boxed headers for widgets. Use thin horizontal separators (1px, 10% opacity) and `label-caps` typography to define sections within a panel.
- **Inputs:** Darker than the panel background with a subtle highlight on focus. Use monospaced fonts for any numeric input fields.
- **Status Indicators:** Use small, high-chroma dots (8px) paired with text to indicate live connection states.