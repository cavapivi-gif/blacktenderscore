import { cn } from '../../lib/utils'

// ─────────────────────────────────────────────────────────────────────────────
// RainbowButton — animated rainbow border + shimmer
// Adapté aux design tokens existants (fond sombre = --primary, texte = --primary-foreground)
// ─────────────────────────────────────────────────────────────────────────────

const rainbowCSS = `
@keyframes rbi-spin {
  0%   { --rbi-angle: 0deg   }
  100% { --rbi-angle: 360deg }
}
@property --rbi-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}
.rbi-btn {
  --rbi-size: 2px;
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
  outline: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 0.75rem;
  gap: 0.375rem;
  transition: opacity 0.2s, transform 0.15s;
  animation: rbi-spin 3s linear infinite;
  background: conic-gradient(
    from var(--rbi-angle),
    #ff6b6b, #ffc06b, #6bffb8, #6bb8ff, #c06bff, #ff6b6b
  );
  padding: var(--rbi-size);
}
.rbi-btn:disabled { opacity: 0.5; pointer-events: none }
.rbi-btn:hover    { opacity: 0.9 }
.rbi-btn:active   { transform: scale(0.97) }
.rbi-inner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  width: 100%;
  height: 100%;
  border-radius: calc(var(--radius) - 2px);
  background: var(--primary);
  color: var(--primary-foreground);
  padding: 0.375rem 0.875rem;
  font-size: inherit;
  font-weight: inherit;
  white-space: nowrap;
}
`

export function RainbowButton({ className, children, ...props }) {
  return (
    <>
      <style>{rainbowCSS}</style>
      <button className={`rbi-btn ${className ?? ''}`} {...props}>
        <span className="rbi-inner">{children}</span>
      </button>
    </>
  )
}
