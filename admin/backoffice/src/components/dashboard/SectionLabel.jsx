/** Titre de section avec sous-titre optionnel et action (lien) */
export function SectionLabel({ children, sub, action }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <div>
        <span className="text-[11px] text-muted-foreground uppercase tracking-widest" style={{ fontWeight: 500 }}>
          {children}
        </span>
        {sub && (
          <span className="ml-2 text-[10px] text-muted-foreground normal-case tracking-normal">
            {sub}
          </span>
        )}
      </div>
      {action}
    </div>
  )
}
