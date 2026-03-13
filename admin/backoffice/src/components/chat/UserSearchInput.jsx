/**
 * UserSearchInput — champ de recherche d'utilisateurs WP pour l'invitation.
 * Live search avec debounce 300ms, résultats en dropdown.
 */
import { useState, useEffect, useRef } from 'react'
import { Search, Xmark } from 'iconoir-react'

export function UserSearchInput({ onSelect, excludeIds = [] }) {
  const [query,   setQuery]   = useState('')
  const [results, setResults] = useState([])
  const [open,    setOpen]    = useState(false)
  const [loading, setLoading] = useState(false)
  const timerRef = useRef(null)

  useEffect(() => {
    if (query.length < 2) { setResults([]); setOpen(false); return }

    clearTimeout(timerRef.current)
    timerRef.current = setTimeout(async () => {
      setLoading(true)
      try {
        const { rest_url, nonce } = window.btBackoffice || {}
        const res = await fetch(
          `${rest_url}/users/search?q=${encodeURIComponent(query)}&exclude=${excludeIds.join(',')}`,
          { headers: { 'X-WP-Nonce': nonce } }
        )
        const data = await res.json()
        setResults(data.users ?? [])
        setOpen(true)
      } catch {
        setResults([])
      } finally {
        setLoading(false)
      }
    }, 300)

    return () => clearTimeout(timerRef.current)
  }, [query, excludeIds.join(',')])

  function select(user) {
    onSelect(user)
    setQuery('')
    setResults([])
    setOpen(false)
  }

  return (
    <div className="relative">
      <div className="flex items-center gap-2 px-3 py-2 rounded-lg border bg-background focus-within:ring-1 focus-within:ring-foreground/20">
        {loading
          ? <span className="w-3.5 h-3.5 border-2 border-muted border-t-foreground/50 rounded-full animate-spin shrink-0" />
          : <Search width={13} height={13} className="text-muted-foreground shrink-0" />
        }
        <input
          type="text"
          value={query}
          onChange={e => setQuery(e.target.value)}
          placeholder="Rechercher un utilisateur…"
          className="flex-1 text-xs bg-transparent outline-none placeholder:text-muted-foreground"
        />
        {query && (
          <button onClick={() => { setQuery(''); setOpen(false) }} className="text-muted-foreground hover:text-foreground">
            <Xmark width={12} height={12} />
          </button>
        )}
      </div>

      {open && results.length > 0 && (
        <div className="absolute z-50 top-full mt-1 w-full rounded-lg border bg-card shadow-lg overflow-hidden">
          {results.map(u => (
            <button
              key={u.id}
              onClick={() => select(u)}
              className="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-muted/50 transition-colors"
            >
              <img src={u.avatar} alt="" className="w-6 h-6 rounded-full shrink-0" />
              <div className="min-w-0">
                <p className="text-xs font-medium truncate">{u.name}</p>
                <p className="text-[10px] text-muted-foreground truncate">{u.email}</p>
              </div>
            </button>
          ))}
        </div>
      )}

      {open && results.length === 0 && query.length >= 2 && !loading && (
        <div className="absolute z-50 top-full mt-1 w-full rounded-lg border bg-card px-3 py-2 text-xs text-muted-foreground shadow">
          Aucun utilisateur trouvé.
        </div>
      )}
    </div>
  )
}
