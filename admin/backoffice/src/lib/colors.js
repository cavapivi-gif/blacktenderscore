/**
 * Génère une couleur d'avatar déterministe à partir d'un nom.
 * 10 palettes harmonieuses, choix par hash DJB2.
 */
const PALETTES = [
  { bg: '#dbeafe', text: '#1e40af' }, // blue
  { bg: '#dcfce7', text: '#166534' }, // green
  { bg: '#fce7f3', text: '#9d174d' }, // pink
  { bg: '#fff7ed', text: '#9a3412' }, // orange
  { bg: '#f3e8ff', text: '#6b21a8' }, // purple
  { bg: '#ecfdf5', text: '#065f46' }, // emerald
  { bg: '#ffe4e6', text: '#9f1239' }, // rose
  { bg: '#fef9c3', text: '#854d0e' }, // yellow
  { bg: '#e0f2fe', text: '#0c4a6e' }, // sky
  { bg: '#fdf4ff', text: '#86198f' }, // fuchsia
]

export function avatarColor(name = '') {
  if (!name?.trim()) return PALETTES[0]
  const hash = [...name.trim()].reduce((h, c) => (h * 31 + c.charCodeAt(0)) | 0, 0)
  return PALETTES[Math.abs(hash) % PALETTES.length]
}
