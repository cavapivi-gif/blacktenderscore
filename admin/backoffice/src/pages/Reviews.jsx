import { Download, Trash, RefreshDouble, Star, CandlestickChart, Group } from 'iconoir-react'
import { PageHeader, Notice, Spinner, Btn } from '../components/ui'
import { PeriodPicker } from '../components/PeriodPicker'
import ReviewsImporter from '../components/ReviewsImporter'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import {
  OverviewTab,
  ProductsTab,
  BehaviorTab,
  ReviewsToolbar,
  ReviewsTable,
  useReviews,
  fmtDate,
  buildExportCsv,
} from './reviews'

// ── Page principale ────────────────────────────────────────────────────────────

export default function Reviews() {
  const {
    // Period
    from, to, compareActive, comparePeriod,
    setCompareActive, handlePeriodChange,
    // Stats
    stats, compareStats, statsLoading,
    // List
    data, total, loading, error, page, q, product, ratingFilter, sort, expanded,
    setPage, setQ, setProduct, setRatingFilter, setExpanded, onSort,
    // UI
    showImporter, setShowImporter, resetting, activeTab, setActiveTab, perPage,
    // Actions
    load, loadStats, handleReset, handleImportDone,
    // Derived
    products,
  } = useReviews()

  // ── Render ───────────────────────────────────────────────────────────────────

  return (
    <div>
      {/* ── Page Header ───────────────────────────────────────────────────────── */}
      <PageHeader
        title="Avis clients"
        subtitle="Import CSV Regiondo · analyse des avis"
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            <Btn
              variant={showImporter ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setShowImporter(v => !v)}
            >
              {showImporter ? 'Masquer import' : 'Importer CSV'}
            </Btn>
            <Btn variant="secondary" size="sm" onClick={() => buildExportCsv(data)} disabled={!data.length}>
              <Download className="w-4 h-4" /> Export
            </Btn>
            <Btn variant="ghost" size="sm" onClick={() => { load(); loadStats() }} title="Rafraîchir">
              <RefreshDouble className="w-4 h-4" />
            </Btn>
            <Btn variant="danger" size="sm" onClick={handleReset} loading={resetting} disabled={!total}>
              <Trash className="w-4 h-4" /> Réinitialiser
            </Btn>
            <span className="text-xs text-muted-foreground">{total.toLocaleString('fr-FR')} avis</span>
          </div>
        }
      />

      {/* ── Import zone (collapsible) ──────────────────────────────────────────── */}
      {showImporter && (
        <div className="mx-6 mt-5 rounded-xl border bg-card p-5">
          <p className="text-sm font-medium mb-1">Import CSV Regiondo — Avis clients</p>
          <p className="text-xs text-muted-foreground mb-4">
            Exportez vos avis depuis Regiondo et importez-les ici.
            Colonnes requises : N° de commande, Évaluation (note), évaluation (texte).
          </p>
          <ReviewsImporter onDone={handleImportDone} />
        </div>
      )}

      {error && <div className="mx-6 mt-5"><Notice type="error">{error}</Notice></div>}

      {/* ── Analytics Section ──────────────────────────────────────────────────── */}
      <div className="mx-6 mt-6">

        {/* Period + Compare toolbar */}
        <div className="flex items-center gap-3 flex-wrap mb-4">
          <PeriodPicker from={from} to={to} onChange={handlePeriodChange} />
          <div className="h-4 w-px bg-border mx-1 hidden sm:block" />
          <button
            type="button"
            onClick={() => setCompareActive(v => !v)}
            className={
              `px-3 py-1.5 rounded-md text-xs font-medium border transition-colors ` +
              (compareActive
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-border bg-card text-muted-foreground hover:text-foreground hover:border-foreground/40')
            }
          >
            <span className="flex items-center gap-1.5">
              <CandlestickChart className="w-3.5 h-3.5" />
              VS période précédente
            </span>
          </button>
          {compareActive && (
            <span className="text-[11px] text-muted-foreground hidden sm:block">
              Comparé à : {fmtDate(comparePeriod.from)} → {fmtDate(comparePeriod.to)}
            </span>
          )}
        </div>

        {/* Analytics Tabs */}
        {statsLoading && !stats ? (
          <div className="flex items-center justify-center py-20 rounded-xl border bg-card">
            <Spinner size={20} />
          </div>
        ) : (
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
              <TabsList>
                <TabsTrigger value="overview">
                  <Star className="w-3.5 h-3.5 mr-1.5" /> Vue d'ensemble
                </TabsTrigger>
                <TabsTrigger value="products">
                  <Group className="w-3.5 h-3.5 mr-1.5" /> Produits
                </TabsTrigger>
                <TabsTrigger value="behavior">
                  <CandlestickChart className="w-3.5 h-3.5 mr-1.5" /> Comportement
                </TabsTrigger>
              </TabsList>
            </div>

            <TabsContent value="overview">
              <OverviewTab
                stats={stats}
                compareStats={compareStats}
                compareActive={compareActive}
                from={from}
                to={to}
              />
            </TabsContent>

            <TabsContent value="products">
              <ProductsTab
                stats={stats}
                compareStats={compareStats}
                compareActive={compareActive}
              />
            </TabsContent>

            <TabsContent value="behavior">
              <BehaviorTab stats={stats} />
            </TabsContent>
          </Tabs>
        )}
      </div>

      {/* ── Reviews Table ──────────────────────────────────────────────────────── */}
      <div className="mx-6 mt-8">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-semibold mb-3">
          Liste des avis
        </p>

        {/* Filters */}
        <ReviewsToolbar
          q={q}
          setQ={setQ}
          product={product}
          setProduct={setProduct}
          ratingFilter={ratingFilter}
          setRatingFilter={setRatingFilter}
          setPage={setPage}
          products={products}
          total={total}
        />

        {/* Table */}
        <ReviewsTable
          data={data}
          loading={loading}
          total={total}
          page={page}
          perPage={perPage}
          sort={sort}
          onSort={onSort}
          setPage={setPage}
          expanded={expanded}
          setExpanded={setExpanded}
        />
      </div>

      {/* Bottom spacing */}
      <div className="h-10" />
    </div>
  )
}
