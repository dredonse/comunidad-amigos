<?php

declare(strict_types=1);

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushHelper
{
    /**
     * @param int[] $userIds
     */
    public static function sendNotification(
        array $userIds,
        string $title,
        string $body,
        string $url
    ): void {
        ob_start();

        try {
            $publicKey  = env('VAPID_PUBLIC_KEY', '');
            $privateKey = env('VAPID_PRIVATE_KEY', '');
            $subject    = env('VAPID_SUBJECT', 'mailto:admin@comunidad.local');

            if ($publicKey === '' || $privateKey === '') {
                return;
            }

            if (!class_exists(WebPush::class)) {
                error_log('PushHelper: minishlink/web-push no instalado (composer install).');

                return;
            }

            $model = new PushModel();
            $rows  = $model->getSubscriptionsByUserIds($userIds);

            if ($rows === []) {
                return;
            }

            if (!str_starts_with($url, 'http')) {
                $url = url(ltrim($url, '/'));
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'url'   => $url,
                'icon'  => url('img/icon-192.png'),
            ], JSON_UNESCAPED_UNICODE);

            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => $subject,
                    'publicKey'  => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ]);

            foreach ($rows as $row) {
                $subscription = Subscription::create([
                    'endpoint' => $row['endpoint'],
                    'keys'     => [
                        'p256dh' => $row['p256dh'],
                        'auth'   => $row['auth'],
                    ],
                ]);
                $webPush->queueNotification($subscription, $payload);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                $endpoint = $report->getEndpoint();
                $reason   = $report->getReason();
                error_log('PushHelper: fallo envío a ' . $endpoint . ' — ' . $reason);

                if ($report->isSubscriptionExpired()) {
                    $model->deleteByEndpoint($endpoint);
                }
            }
        } catch (Throwable $e) {
            error_log('PushHelper: ' . $e->getMessage());
        } finally {
            ob_end_clean();
        }
    }
}
