import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../components/Tabs'
import { CssEditor, JsEditor } from './shared'

export default function CssSection({ settings, set }) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Code injecté dans chaque{' '}
        <code className="bg-muted px-1 py-0.5 rounded text-[11px]">{'<booking-widget>'}</code>{' '}
        Regiondo sur le front. S'applique à tous les widgets de réservation.
      </p>
      <Tabs defaultValue="css">
        <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto">
          <TabsTrigger value="css" className="rounded-none rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white data-[state=active]:text-foreground">
            CSS
            {settings.booking_custom_css?.trim() && (
              <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
            )}
          </TabsTrigger>
          <TabsTrigger value="js" className="rounded-none rounded-tr-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white data-[state=active]:text-foreground">
            JavaScript
            {settings.booking_custom_js?.trim() && (
              <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
            )}
          </TabsTrigger>
        </TabsList>
        <TabsContent value="css" className="mt-0 flex flex-col gap-2">
          <CssEditor
            value={settings.booking_custom_css ?? ''}
            onChange={v => set('booking_custom_css', v)}
            placeholder={`.regiondo-widget .regiondo-button-addtocart {\n  border-radius: 40px;\n  background: #222;\n}`}
          />
        </TabsContent>
        <TabsContent value="js" className="mt-0">
          <JsEditor
            value={settings.booking_custom_js ?? ''}
            onChange={v => set('booking_custom_js', v)}
          />
        </TabsContent>
      </Tabs>
    </div>
  )
}
