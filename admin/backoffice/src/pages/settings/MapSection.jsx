import { NavLink } from 'react-router-dom'
import { Notice } from '../../components/ui'
import MapPresets from '../../components/MapPresets'

export default function MapSection({ settings, set }) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Choisissez un preset ou créez le vôtre. Le style actif est injecté sur toutes les pages
        contenant un widget{' '}
        <span className="text-xs bg-muted rounded px-1 mx-0.5 font-mono">bt-itinerary</span>
        ou{' '}
        <span className="text-xs bg-muted rounded px-1 mx-0.5 font-mono">google_maps</span>{' '}
        avec "Appliquer le style de carte" activé.
      </p>
      {!settings.snazzymaps_api_key && (
        <Notice type="warn">
          Clé Snazzy Maps non configurée — rendez-vous dans{' '}
          <NavLink to="/settings/api" className="underline font-medium">Connexion API → Snazzy Maps</NavLink>{' '}
          pour l'ajouter.
        </Notice>
      )}
      <MapPresets
        presets={settings.map_presets ?? []}
        activeJson={settings.map_style_json ?? ''}
        apiKey={settings.maps_api_key ?? ''}
        snazzymapsEnabled={!!(settings.snazzymaps_api_key)}
        onPresetsChange={v => set('map_presets', v)}
        onActivate={v => set('map_style_json', v)}
      />
    </div>
  )
}
