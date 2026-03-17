<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Regiondo\Client;

defined('ABSPATH') || exit;

/**
 * Synchronise les avis Regiondo (API /reviews) vers la table bt_reviews.
 *
 * Itère sur tous les produits, pagine les avis pour chacun,
 * mappe le format API vers le schéma ReviewsDb, et upsert par review_id.
 */
class ReviewsSync {

    private Client    $client;
    private ReviewsDb $db;

    public function __construct() {
        $this->client = new Client();
        $this->db     = new ReviewsDb();
    }

    /**
     * Sync all reviews from Regiondo API.
     *
     * @return array { fetched, inserted, updated, skipped, errors, products_scanned }
     */
    public function sync_all(): array {
        $this->db->ensure_table();

        $products = $this->client->get_products('fr-FR');
        $all_items = [];

        foreach ($products as $product) {
            $product_id   = $product['product_id'] ?? $product['id'] ?? null;
            $product_name = $product['name'] ?? $product['title'] ?? '';
            if (!$product_id) continue;

            $page = 1;
            do {
                $response = $this->client->get_reviews([
                    'product_id' => $product_id,
                    'limit'      => 250,
                    'page'       => $page,
                ]);

                foreach ($response['data'] as $review) {
                    $mapped = self::map_review($review, $product_name);
                    if ($mapped) $all_items[] = $mapped;
                }

                $total_pages = $response['total_pages'] ?? 1;
                $page++;
            } while ($page <= $total_pages);
        }

        $fetched = count($all_items);

        if (empty($all_items)) {
            return [
                'fetched' => 0, 'inserted' => 0, 'updated' => 0,
                'skipped' => 0, 'errors' => [],
                'products_scanned' => count($products),
            ];
        }

        // Upsert via ReviewsDb::import_batch (chunks of 200)
        $totals = [
            'fetched' => $fetched, 'inserted' => 0, 'updated' => 0,
            'skipped' => 0, 'errors' => [],
            'products_scanned' => count($products),
        ];

        foreach (array_chunk($all_items, 200) as $batch) {
            $result = $this->db->import_batch($batch);
            $totals['inserted'] += $result['inserted'] ?? 0;
            $totals['updated']  += $result['updated']  ?? 0;
            $totals['skipped']  += $result['skipped']  ?? 0;
            if (!empty($result['errors'])) {
                $totals['errors'] = array_merge($totals['errors'], $result['errors']);
            }
        }

        // Store sync status
        update_option('bt_reviews_sync_status', [
            'last_sync'         => current_time('mysql', true),
            'total_in_db'       => $this->db->count_all(),
            'fetched'           => $totals['fetched'],
            'inserted'          => $totals['inserted'],
            'updated'           => $totals['updated'],
            'products_scanned'  => $totals['products_scanned'],
        ], false);

        return $totals;
    }

    /**
     * Map a Regiondo review API object to ReviewsDb schema.
     *
     * API format:
     *   review_id, title, detail, nickname, created_at,
     *   vote_details: [{ value, rating_code }],
     *   responses: [{ nickname, message, created_at }]
     */
    private static function map_review(array $review, string $product_name = ''): ?array {
        $review_id = $review['review_id'] ?? '';
        if (empty($review_id)) return null;

        // Extract overall rating from vote_details
        $rating = null;
        foreach ($review['vote_details'] ?? [] as $vote) {
            $code = strtolower($vote['rating_code'] ?? '');
            if ($code === 'overall rating' || $code === 'overall') {
                $rating = (int) ($vote['value'] ?? 0);
                break;
            }
        }
        // Fallback: average all ratings
        if ($rating === null && !empty($review['vote_details'])) {
            $sum = 0;
            $cnt = 0;
            foreach ($review['vote_details'] as $vote) {
                $v = (int) ($vote['value'] ?? 0);
                if ($v > 0) { $sum += $v; $cnt++; }
            }
            $rating = $cnt > 0 ? (int) round($sum / $cnt) : null;
        }

        // Extract first staff response
        $response      = '';
        $employee_name = '';
        if (!empty($review['responses']) && is_array($review['responses'])) {
            $first_response = $review['responses'][0];
            $response       = $first_response['message']  ?? '';
            $employee_name  = $first_response['nickname'] ?? '';
        }

        // Parse review date
        $review_date = $review['created_at'] ?? '';
        if ($review_date && strpos($review_date, ' ') !== false) {
            $review_date = explode(' ', $review_date)[0]; // YYYY-MM-DD
        }

        return [
            'order_number'   => (string) $review_id,
            'product_name'   => $product_name,
            'review_date'    => $review_date,
            'customer_name'  => $review['nickname'] ?? '',
            'rating'         => $rating,
            'review_title'   => $review['title'] ?? '',
            'review_body'    => $review['detail'] ?? '',
            'review_status'  => 'published',
            'employee_name'  => $employee_name,
            'response'       => $response,
        ];
    }
}
