<?php

declare(strict_types=1);

namespace Services;

use Throwable;

/**
 * Cliente mínimo da API compatível com OpenAI da Groq.
 *
 * A chave fica no servidor. Falha de rede, chave inválida ou limite de taxa
 * devolvem null; a página continua com o retrato-template.
 */
final class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private int $timeout;
    private int $maxTokens;
    private float $temperature;
    private bool $enabled;
    private string $ultimoErro = '';

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->apiKey = trim((string) ($config['api_key'] ?? ''));
        $this->model = trim((string) ($config['model'] ?? 'qwen/qwen3.8-27b'));
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.groq.com/openai/v1'), '/');
        $this->timeout = max(5, (int) ($config['timeout'] ?? 30));
        $this->maxTokens = max(200, (int) ($config['max_tokens'] ?? 900));
        $this->temperature = (float) ($config['temperature'] ?? 0.75);
        $this->enabled = (bool) ($config['enabled'] ?? true);
    }

    public function disponivel(): bool
    {
        return $this->enabled && $this->apiKey !== '';
    }

    public function modelo(): string
    {
        return $this->model;
    }

    /** Detalhe da última falha (HTTP/curl), para logs e diagnóstico. */
    public function ultimoErro(): string
    {
        return $this->ultimoErro;
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return array{texto: string, modelo: string, tokens: int|null}|null
     */
    public function chat(array $messages, ?int $maxTokens = null, ?float $temperature = null): ?array
    {
        if (!$this->disponivel()) {
            $this->ultimoErro = 'desativado ou sem chave';

            return null;
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature ?? $this->temperature,
            'max_tokens' => $maxTokens ?? $this->maxTokens,
        ];

        $curl = curl_init($this->baseUrl . '/chat/completions');

        if ($curl === false) {
            $this->ultimoErro = 'curl_init falhou';

            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_USERAGENT => 'PersonaCut/1.0',
        ]);

        $corpo = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($corpo === false || $status !== 200) {
            $this->ultimoErro = 'HTTP ' . $status
                . ($erro !== '' ? ' | curl: ' . $erro : '')
                . ' | ' . substr((string) $corpo, 0, 600);

            error_log(sprintf(
                '[PersonaCut][groq] HTTP %d%s',
                $status,
                $erro !== '' ? ' — ' . $erro : ''
            ));

            return null;
        }

        $this->ultimoErro = '';
        $dados = json_decode($corpo, true);
        $conteudo = $dados['choices'][0]['message']['content'] ?? null;

        if (!is_string($conteudo) || trim($conteudo) === '') {
            $this->ultimoErro = 'resposta sem conteúdo';

            error_log('[PersonaCut][groq] resposta sem conteúdo');

            return null;
        }

        return [
            'texto' => trim($conteudo),
            'modelo' => (string) ($dados['model'] ?? $this->model),
            'tokens' => isset($dados['usage']['total_tokens'])
                ? (int) $dados['usage']['total_tokens']
                : null,
        ];
    }
}
