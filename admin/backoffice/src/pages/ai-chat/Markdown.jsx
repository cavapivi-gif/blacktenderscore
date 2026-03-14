// ─────────────────────────────────────────────────────────────────────────────
// Markdown renderer
// ─────────────────────────────────────────────────────────────────────────────

function Inline({ t }) {
  if (!t) return null
  const tokens = []
  let rest = t, k = 0
  while (rest.length) {
    const bold   = rest.match(/^([\s\S]*?)\*\*(.+?)\*\*/)
    const italic = rest.match(/^([\s\S]*?)(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/)
    const code   = rest.match(/^([\s\S]*?)`(.+?)`/)
    const all    = [bold, italic, code].filter(Boolean)
    if (!all.length) { tokens.push(<span key={k++}>{rest}</span>); break }
    const pick = all.reduce((a, b) => (a[1].length <= b[1].length ? a : b))
    if (pick[1]) tokens.push(<span key={k++}>{pick[1]}</span>)
    if (pick === bold)        tokens.push(<strong key={k++} className="font-semibold text-foreground">{pick[2]}</strong>)
    else if (pick === italic) tokens.push(<em key={k++}>{pick[2]}</em>)
    else                      tokens.push(<code key={k++} className="bg-neutral-100 px-1 py-0.5 rounded text-[0.82em] font-mono">{pick[2]}</code>)
    rest = rest.slice(pick[0].length)
  }
  return <>{tokens}</>
}

export function Markdown({ content }) {
  if (!content) return null
  const lines = content.split('\n')
  const blocks = []
  let i = 0
  while (i < lines.length) {
    const line = lines[i]
    if (line.startsWith('```')) {
      const cl = []; i++
      while (i < lines.length && !lines[i].startsWith('```')) { cl.push(lines[i]); i++ }
      blocks.push({ t: 'code', c: cl.join('\n') }); i++; continue
    }
    const h = line.match(/^(#{1,3})\s+(.+)$/)
    if (h) { blocks.push({ t: 'h', lvl: h[1].length, c: h[2] }); i++; continue }
    if (/^[-*•]\s/.test(line)) {
      const items = []
      while (i < lines.length && /^[-*•]\s/.test(lines[i])) { items.push(lines[i].replace(/^[-*•]\s+/, '')); i++ }
      blocks.push({ t: 'ul', items }); continue
    }
    if (/^\d+\.\s/.test(line)) {
      const items = []
      while (i < lines.length && /^\d+\.\s/.test(lines[i])) { items.push(lines[i].replace(/^\d+\.\s+/, '')); i++ }
      blocks.push({ t: 'ol', items }); continue
    }
    if (/^>\s?/.test(line)) {
      const rows = []
      while (i < lines.length && /^>\s?/.test(lines[i])) { rows.push(lines[i].replace(/^>\s?/, '')); i++ }
      blocks.push({ t: 'bq', rows }); continue
    }
    if (line.startsWith('|')) {
      const rows = []
      while (i < lines.length && lines[i].startsWith('|')) { rows.push(lines[i]); i++ }
      const parseRow = r => r.split('|').filter((_, j, a) => j > 0 && j < a.length - 1).map(c => c.trim())
      if (rows.length >= 2) {
        const headers = parseRow(rows[0])
        const data = rows.slice(2).map(parseRow)
        blocks.push({ t: 'table', headers, data })
      }
      continue
    }
    if (!line.trim()) { i++; continue }
    const para = []
    while (i < lines.length && lines[i].trim() && !lines[i].startsWith('```') && !lines[i].startsWith('#') && !/^[-*•]\s/.test(lines[i]) && !/^\d+\.\s/.test(lines[i]) && !/^>\s?/.test(lines[i]) && !lines[i].startsWith('|')) {
      para.push(lines[i]); i++
    }
    if (para.length) blocks.push({ t: 'p', c: para.join('\n') })
  }
  return (
    <div className="space-y-2.5 text-base leading-relaxed text-foreground">
      {blocks.map((b, idx) => {
        if (b.t === 'code') return (
          <pre key={idx} className="bg-neutral-950 text-neutral-100 rounded-xl px-4 py-3 overflow-x-auto text-[13px] leading-relaxed font-mono my-1">
            <code>{b.c}</code>
          </pre>
        )
        if (b.t === 'h') return (
          <p key={idx} className={`font-semibold mt-3 first:mt-0 ${b.lvl === 1 ? 'text-lg' : 'text-base'}`}>
            <Inline t={b.c} />
          </p>
        )
        if (b.t === 'ul') return (
          <ul key={idx} className="space-y-1.5">
            {b.items.map((item, j) => (
              <li key={j} className="flex gap-2">
                <span className="text-muted-foreground shrink-0 mt-[2px] select-none">—</span>
                <span><Inline t={item} /></span>
              </li>
            ))}
          </ul>
        )
        if (b.t === 'ol') return (
          <ol key={idx} className="space-y-1.5">
            {b.items.map((item, j) => (
              <li key={j} className="flex gap-2">
                <span className="text-muted-foreground shrink-0 font-mono text-[11px] mt-0.5 min-w-[18px] select-none">{j + 1}.</span>
                <span><Inline t={item} /></span>
              </li>
            ))}
          </ol>
        )
        if (b.t === 'bq') {
          const full = b.rows.join(' ')
          const isDanger = /🔴|❌|critique|urgent/i.test(full)
          const isGood   = /✅|💡|recomman/i.test(full)
          const isWarn   = /⚠️|⚡|attention|alerte|inhabituel|anormal/i.test(full)
          const border = isDanger ? '#ef4444' : isWarn ? '#f59e0b' : isGood ? '#10b981' : '#6366f1'
          const bg     = isDanger ? '#fef2f2' : isWarn ? '#fffbeb' : isGood ? '#f0fdf4' : '#eef2ff'
          return (
            <div key={idx} className="pl-3 pr-3 py-2.5 rounded-r-lg my-0.5 space-y-0.5" style={{ borderLeft: `3px solid ${border}`, background: bg }}>
              {b.rows.map((row, j) => <p key={j} className="text-sm leading-relaxed"><Inline t={row} /></p>)}
            </div>
          )
        }
        if (b.t === 'table') return (
          <div key={idx} className="overflow-x-auto rounded-lg border border-border my-1">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-muted/40 border-b border-border">
                  {b.headers.map((h, j) => (
                    <th key={j} className="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {b.data.map((row, j) => (
                  <tr key={j} className="hover:bg-muted/20 transition-colors">
                    {row.map((cell, k) => (
                      <td key={k} className="px-3 py-2 text-xs"><Inline t={cell} /></td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )
        return <p key={idx}><Inline t={b.c} /></p>
      })}
    </div>
  )
}
