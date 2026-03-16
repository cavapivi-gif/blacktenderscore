# Audit UI/UX — Rework SJ Reviews vers le Design System BlackTenders

> Ce document prepare le chantier de migration du backoffice `studiojae-reviews`
> vers le design system, les patterns et le dynamisme de `blacktenderscore`.
> **Aucun code ne doit etre ecrit avant validation de ce plan.**

---

## 1. Comparatif Design Systems

### Tokens couleur

| Token | BlackTenders (BT) | SJ Reviews (SJR) actuel | Action |
|-------|-------------------|------------------------|--------|
| --background | #fbfaf8 (beige chaud) | oklch ~ #fbfaf8 | Identique |
| --foreground | #1a1917 (brun fonce) | oklch ~ #0a0a0a | Aligner sur #1a1917 |
| --primary | #1a1917 | oklch ~ #0a0a0a | Aligner |
| --primary-foreground | #faf9f5 (creme) | oklch ~ #fafafa | Aligner |
| --muted | #f0efe9 | oklch ~ #f0efe9 | OK |
| --muted-foreground | #6b6860 | oklch gris moyen | Aligner |
| --border | #e3e1db (gris chaud) | oklch ~ #e3e1db | OK |
| --radius | 0.5rem (8px) | 0.625rem (10px) | Aligner sur 0.5rem |

**Verdict :** Palettes deja tres proches. Aligner les hex et passer d'oklch a hex.

### Typographie

| | BT | SJR | Action |
|---|---|---|---|
| Font | Buenos Aires (300/700) | Geist Variable | Migrer vers Buenos Aires |
| Mono | JetBrains Mono | systeme | Ajouter JetBrains Mono |

### Icons

| | BT | SJR | Action |
|---|---|---|---|
| Librairie | iconoir-react (npm) | Icons.jsx maison (SVG) | Migrer vers iconoir-react |
| Provider icons | N/A | SVG dans Providers.php | Garder + enrichir |

---

## 2. Comparatif Composants UI

### Composants partages

| Composant | BT | SJR | Action |
|-----------|---|-----|--------|
| Btn | 4 variants, 3 sizes, loading | Idem | Copier BT |
| Badge | 8 variants base | 8 base + provider variants | Fusionner |
| Input | label + error | Idem | Copier BT |
| Toggle | switch vert | Idem | OK |
| Table | sort + render | Idem | OK |
| PageHeader | title/subtitle/actions | Idem | OK |
| StatCard | label/value/sub/accent/legend | label/value/sub | Copier BT (plus riche) |
| DangerModal | title/confirm/cancel | ABSENT (window.confirm) | Ajouter |
| Sheet | slide-over panel | ABSENT | Ajouter |
| Dialog | modal Framer Motion | ABSENT | Ajouter |
| Tabs | compound component | Custom | Aligner |
| Toast | ABSENT | context + container | Garder SJR |
| StarPicker | ABSENT | 5 star selector | Garder SJR |
| DonutChart | ABSENT | recharts pie | Garder SJR |

---

## 3. Patterns de page a aligner

| Pattern BT | Present SJR | Action |
|-----------|-------------|--------|
| KPI cards delta + sparkline | Stats basiques | Migrer KpiCard BT |
| Chips filtres actifs | ABSENT | Ajouter |
| Sheet detail au clic | ABSENT | Ajouter |
| Section labels "Tout voir" | ABSENT | Ajouter |
| Empty state avec CTA | Texte simple | Enrichir |
| Avatar color deterministe | ABSENT | Copier colors.js |
| decodeHtml helper | ABSENT | Copier |
| Filter popup drawer | ABSENT | P3 |

---

## 4. Ecarts fonctionnels critiques

### Dashboard

| Probleme | Impact | Fix |
|----------|--------|-----|
| Header affiche 343 (CPT) au lieu de 1290 (enrichi) | Compte faux | Utiliser sj_enriched_stats total |
| Pas de delta vs periode precedente | Manque info | Ajouter comme BT |
| Lieux sans total agrege | Pas de vue globale | Somme dans header |

### Lieux & Sources

| Probleme | Impact | Fix |
|----------|--------|-----|
| AirBnB/CheckYeti sans icone | Badge gris generique | Icones editables |
| Pas de note pour sources "autre" | Badge absent | Afficher manual_rating |
| Pas de regroupement par source | Vue eclatee | Ajouter groupes |
| Sync sans feedback delta | UX pauvre | Avant/apres |

### Providers

| Probleme | Impact | Fix |
|----------|--------|-----|
| Icones codees en dur PHP | Pas de custom sans code | Settings editables |
| Pas d'icone AirBnB | Lettre "A" grise | Upload SVG/emoji |
| Couleurs fixes | Pas de perso | Picker couleur |

---

## 5. Plan de migration progressif

### Phase 1 — Fondations (tokens + composants)
1. Aligner CSS tokens (hex BT dans index.css SJR)
2. Importer font Buenos Aires (@font-face)
3. Migrer ui.jsx (copier BT + fusionner variants provider)
4. Ajouter Sheet.jsx, Dialog.jsx, DangerModal
5. Installer iconoir-react, garder Icons.jsx custom
6. Copier colors.js (avatarColor) et utils (cn, fmtDate, fmtCurrency)

### Phase 2 — Pages core
7. Dashboard : KPI cards enrichies, total 1290, delta
8. Lieux : icones provider, notes badges, groupement source, total
9. Reviews list : Sheet detail, chips filtres, DangerModal delete

### Phase 3 — Enrichissement
10. Providers page : upload icone, picker couleur, preview
11. Settings : interconnexion dashboard
12. Bridge BlackTenders : REST stats croisees

---

## 6. Fichiers a copier de BT

| Source BT | Destination SJR | Adaptation |
|-----------|----------------|------------|
| src/components/ui.jsx | src/components/ui.jsx | Fusionner variants |
| src/components/Sheet.jsx | Nouveau | Copie directe |
| src/components/Dialog.jsx | Nouveau | Adapter z-index |
| src/lib/colors.js | src/lib/colors.js | Copie directe |
| src/lib/utils.js | Fusionner | cn, fmtDate, fmtCurrency |
| src/index.css (tokens) | Adapter index.css | Hex + scoping |
| Buenos Aires woff2 | src/assets/fonts/ | Copie |

---

## 7. Provider Icons necessaires

| Provider | Actuel | Cible |
|----------|--------|-------|
| Google | SVG "G" | OK (deja bon) |
| TripAdvisor | SVG chouette | OK |
| Facebook | SVG "f" | OK |
| Trustpilot | SVG etoile | OK |
| Regiondo | Lettre "R" | SVG logo |
| Direct | Lettre "D" grise | Icone formulaire |
| AirBnB | Lettre "A" grise | SVG AirBnB logo |
| CheckYeti | Lettre "A" grise | SVG/emoji ski |
| Autre | Lettre "A" grise | Icone globe |

---

## 8. Metriques de succes

| Metrique | Avant | Objectif |
|----------|-------|---------|
| Total dashboard | 343 (CPT) | 1290 (enrichi) |
| Lieux avec icone | 4/8 | 8/8 |
| Lieux avec note badge | 6/8 | 8/8 |
| Composants UI partages | 0 | 15+ |
| Tokens alignes | ~70% | 100% |
| Font identique | Non | Oui |
| Sheet detail avis | Non | Oui |
| DangerModal | Non | Oui |
| Chips filtres | Non | Oui |
| Delta KPI | Partiel | Complet |

---

## 9. Risques

| Risque | Mitigation |
|--------|------------|
| TW v4 (SJR) vs v3 (BT) | Classes identiques, config differente |
| oklch vs hex | Remplacer oklch par hex |
| shadcn sidebar SJR vs custom BT | Garder shadcn (plus riche) |
| Build root-owned | Pattern mv+cp comme BT |
| Font Buenos Aires absente | Copier woff2 |
| Scopes window differents | Garder independants |

---

*Document genere le 2026-03-16. A valider avant implementation.*
