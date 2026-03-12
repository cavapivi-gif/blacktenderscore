/**
 * Minimal MapPresets stub — preset picker for Google Maps JSON styles.
 */
export default function MapPresets({ onSelect }) {
  const presets = [
    { name: 'Standard', value: '[]' },
    { name: 'Silver', value: '[{"elementType":"geometry","stylers":[{"color":"#f5f5f5"}]}]' },
    { name: 'Dark', value: '[{"elementType":"geometry","stylers":[{"color":"#212121"}]}]' },
  ]

  return (
    <div className="flex flex-wrap gap-2">
      {presets.map(p => (
        <button
          key={p.name}
          type="button"
          onClick={() => onSelect?.(p.value)}
          className="px-3 py-1.5 text-xs font-medium rounded-md border border-input bg-background text-foreground hover:bg-accent transition-colors"
        >
          {p.name}
        </button>
      ))}
    </div>
  )
}
