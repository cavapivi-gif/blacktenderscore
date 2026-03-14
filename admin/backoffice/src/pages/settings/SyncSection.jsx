import { INTERVALS } from './shared'

export default function SyncSection({ settings, set, syncPostType }) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Importe automatiquement les produits Regiondo vers des posts WordPress.
      </p>
      <label className="flex flex-col gap-1.5">
        <span className="text-sm font-medium">Fréquence</span>
        <select
          value={settings.sync_interval ?? 0}
          onChange={e => set('sync_interval', Number(e.target.value))}
          className="flex h-9 w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {INTERVALS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
      </label>
      <label className="flex flex-col gap-1.5">
        <span className="text-sm font-medium">Type de post cible</span>
        <select
          value={syncPostType}
          onChange={e => set('post_types', [e.target.value])}
          className="flex h-9 w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {(settings.all_post_types ?? []).map(pt => (
            <option key={pt.name} value={pt.name}>{pt.label} ({pt.name})</option>
          ))}
        </select>
      </label>
      {settings.sync_next_run && (
        <p className="text-xs text-muted-foreground">
          Prochaine sync : {new Date(settings.sync_next_run * 1000).toLocaleString('fr-FR')}
        </p>
      )}
    </div>
  )
}
