<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = rtrim(env('SUPABASE_URL'), '/');
        $this->key = env('SUPABASE_ANON_KEY');
    }

    /**
     * Pobierz dane z tabeli
     */
    public function select(string $table, array $filters = [], string $columns = '*')
    {
        $query = http_build_query(array_merge(['select' => $columns], $filters));

        $response = Http::withHeaders($this->headers())
            ->get("{$this->url}/rest/v1/{$table}?{$query}");

        return $this->handleResponse($response);
    }

    /**
     * Wstaw dane do tabeli
     */
    public function insert(string $table, array $data)
    {
        $response = Http::withHeaders($this->headers(true))
            ->post("{$this->url}/rest/v1/{$table}", $data);

        return $this->handleResponse($response);
    }

    /**
     * Zaktualizuj rekord
     */
    public function update(string $table, array $filters, array $data)
    {
        $query = http_build_query($filters);

        $response = Http::withHeaders($this->headers(true))
            ->patch("{$this->url}/rest/v1/{$table}?{$query}", $data);

        return $this->handleResponse($response);
    }

    /**
     * Usuń rekord
     */
    public function delete(string $table, array $filters)
    {
        $query = http_build_query($filters);

        $response = Http::withHeaders($this->headers(true))
            ->delete("{$this->url}/rest/v1/{$table}?{$query}");

        return $this->handleResponse($response);
    }

    /**
     * Pomocnicze: nagłówki autoryzacji
     */
    protected function headers(bool $isJson = false)
    {
        $headers = [
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];

        if ($isJson) {
            $headers['Content-Type'] = 'application/json';
            $headers['Prefer'] = 'return=representation';
        }

        return $headers;
    }

    /**
     * Pomocnicze: obsługa odpowiedzi
     */
    protected function handleResponse($response)
    {
        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body(),
        ];
    }
}
