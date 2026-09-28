<?php

namespace App\Services;

use App\Models\MetaConversionEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MetaConversionsApi
{
    public function configured(): bool
    {
        return (bool) config('meta.enabled')
            && filled(config('meta.access_token'))
            && preg_match('/^v\d+\.\d+$/', (string) config('meta.graph_version')) === 1;
    }

    /** Never throw/log remote response bodies, request data, or credential-bearing exceptions. */
    public function send(MetaConversionEvent $event): array
    {
        if (! $this->configured()) {
            return ['status' => 'paused', 'error' => 'unconfigured'];
        }
        $snapshot = $event->payload;
        $body = ['data' => [$snapshot['event']]];
        if (! empty($snapshot['test_event_code'])) {
            $body['test_event_code'] = $snapshot['test_event_code'];
        }
        try {
            $response = Http::withToken(config('meta.access_token'))->acceptJson()
                ->connectTimeout(3)->timeout(10)->withoutRedirecting()
                ->post('https://graph.facebook.com/'.config('meta.graph_version').'/'.$event->pixel_id.'/events', $body);
        } catch (ConnectionException) {
            return ['status' => 'retry', 'error' => 'network'];
        } catch (\Throwable) {
            return ['status' => 'failed', 'error' => 'transport_error'];
        }

        if ($response->successful() && (int) $response->json('events_received') === 1 && ! $response->json('error')) {
            return ['status' => 'sent', 'error' => null];
        }
        $status = $response->status();
        // Authentication/configuration failures remain permanent, even with a transient hint.
        $authError = in_array((int) $response->json('error.code'), [102, 190], true);
        $retry = ! $authError && ! in_array($status, [401, 403], true)
            && ($status >= 500 || in_array($status, [408, 429], true)
                || $response->json('error.is_transient') === true || $response->successful());

        return ['status' => $retry ? 'retry' : 'failed', 'error' => 'http_'.$status];
    }
}
