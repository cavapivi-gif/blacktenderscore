<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Regiondo\Client;

defined('ABSPATH') || exit;

/**
 * Synchronise les solditems Regiondo vers la table bt_reservations.
 *
 * Utilise /supplier/solditems (le seul endpoint qui fonctionne pour les comptes supplier).
 * Mappe les champs de l'API vers le schéma de ReservationDb::upsert().
 */
class ReservationSync {

    private Client       $client;
    private ReservationDb $db;

    public function __construct() {
        $this->client = new Client();
        $this->db     = new ReservationDb();
    }

    /**
     * Import solditems for a date range.
     * Paginates automatically (250 per page).
     *
     * @param string $from YYYY-MM-DD
     * @param string $to   YYYY-MM-DD
     * @return array { fetched: int, inserted: int, updated: int, skipped: int, errors: string[] }
     */
    public function import_period(string $from, string $to): array {
        $all_items = [];
        $offset    = 0;
        $limit     = 250;

        // Paginate through all solditems
        do {
            $response = $this->client->get_sold_items([
                'from'   => $from,
                'to'     => $to,
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            $items = $response['data'] ?? [];
            $total = $response['total'] ?? 0;

            foreach ($items as $item) {
                $mapped = self::map_solditem($item);
                if ($mapped) $all_items[] = $mapped;
            }

            $offset += $limit;
        } while (count($items) === $limit && $offset < $total);

        $fetched = count($all_items);

        if (empty($all_items)) {
            return ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        }

        // Upsert in batches of 500
        $totals = ['fetched' => $fetched, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach (array_chunk($all_items, 500) as $batch) {
            $result = $this->db->upsert($batch);
            $totals['inserted'] += $result['inserted'] ?? 0;
            $totals['updated']  += $result['updated']  ?? 0;
            $totals['skipped']  += $result['skipped']  ?? 0;
            if (!empty($result['errors'])) {
                $totals['errors'] = array_merge($totals['errors'], $result['errors']);
            }
        }

        return $totals;
    }

    /**
     * Map a Regiondo solditem API object to our DB schema.
     *
     * Regiondo solditems fields (observed):
     *   calendar_sold_id, order_increment_id, product_name, offer_name,
     *   price, quantity, date_bought, activity_date, activity_time,
     *   buyer_name, buyer_email, channel_name, booking_status,
     *   payment_method, payment_status, booking_key
     */
    private static function map_solditem(array $item): ?array {
        $cal_id = $item['calendar_sold_id'] ?? $item['id'] ?? '';
        if (empty($cal_id)) return null;

        // activity_date = the date of the activity/excursion (appointment)
        // date_bought = the date the purchase was made (created_at)
        $activity_date = $item['activity_date'] ?? $item['event_date'] ?? null;
        $date_bought   = $item['date_bought']   ?? $item['created_at'] ?? $item['booking_date'] ?? null;

        // Price: Regiondo may send as string or float
        $price = $item['price'] ?? $item['price_total'] ?? $item['total_price'] ?? null;
        if ($price !== null) $price = (float) $price;

        // Build offer_raw from product + offer info
        $offer_parts = array_filter([
            $item['product_name'] ?? '',
            $item['offer_name']   ?? $item['offer_raw'] ?? '',
        ]);
        $offer_raw = implode(' — ', $offer_parts);

        return [
            'calendar_sold_id'   => (string) $cal_id,
            'order_increment_id' => $item['order_increment_id'] ?? $item['order_id'] ?? null,
            'created_at'         => $date_bought,
            'offer_raw'          => $offer_raw ?: null,
            'product_name'       => $item['product_name'] ?? '',
            'quantity'           => max(1, (int) ($item['quantity'] ?? 1)),
            'price_total'        => $price,
            'buyer_name'         => $item['buyer_name']  ?? $item['customer_name']  ?? '',
            'buyer_email'        => $item['buyer_email'] ?? $item['customer_email'] ?? '',
            'appointment_date'   => $activity_date,
            'channel'            => $item['channel_name'] ?? $item['channel'] ?? '',
            'booking_status'     => $item['booking_status'] ?? $item['status'] ?? '',
            'payment_method'     => $item['payment_method'] ?? '',
            'payment_status'     => $item['payment_status'] ?? '',
            'booking_key'        => $item['booking_key'] ?? '',
        ];
    }
}
