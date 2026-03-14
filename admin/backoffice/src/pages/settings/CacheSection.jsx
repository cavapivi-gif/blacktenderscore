import { Input, Btn } from '../../components/ui'

export default function CacheSection({ settings, set, flushing, handleFlush }) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Les réponses Regiondo sont mises en cache pour éviter les appels répétés.
      </p>
      <div className="flex items-end gap-3">
        <div className="w-36">
          <Input
            label="Durée (secondes)"
            type="number"
            min={60}
            value={settings.cache_ttl ?? 3600}
            onChange={e => set('cache_ttl', Number(e.target.value))}
          />
        </div>
        <Btn variant="secondary" loading={flushing} onClick={handleFlush}>
          Vider maintenant
        </Btn>
      </div>
      <p className="text-xs text-muted-foreground">3600 = 1 heure (recommandé)</p>
    </div>
  )
}
