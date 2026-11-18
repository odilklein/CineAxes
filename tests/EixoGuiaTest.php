<?php

declare(strict_types=1);

use Models\EixoGuia;

require_once __DIR__ . '/_assert.php';

teste('guia cobre os oito eixos', function (): void {
    $esperados = ['estetica', 'tom', 'universo', 'ritmo', 'moralidade', 'estrutura', 'atmosfera', 'epoca'];
    $guias = EixoGuia::todos();

    igual(count($esperados), count($guias));

    foreach ($esperados as $slug) {
        afirmar(isset($guias[$slug]), 'faltou o guia de ' . $slug);
    }
});

teste('cada guia tem resumo, parágrafos, leitura, cenas e citações', function (): void {
    foreach (EixoGuia::todos() as $slug => $guia) {
        afirmar(trim((string) ($guia['resumo'] ?? '')) !== '', $slug . ': resumo vazio');
        afirmar(count($guia['paragrafos'] ?? []) >= 2, $slug . ': menos de dois parágrafos');
        afirmar(trim((string) ($guia['leitura']['esq'] ?? '')) !== '', $slug . ': leitura do polo esquerdo vazia');
        afirmar(trim((string) ($guia['leitura']['dir'] ?? '')) !== '', $slug . ': leitura do polo direito vazia');
        afirmar(count($guia['cenas'] ?? []) >= 3, $slug . ': menos de três cenas');
        afirmar(count($guia['citacoes'] ?? []) >= 2, $slug . ': menos de duas citações');
    }
});

teste('cenas e citações têm os campos que o modal usa', function (): void {
    foreach (EixoGuia::todos() as $slug => $guia) {
        foreach ($guia['cenas'] as $i => $cena) {
            foreach (['obra', 'ano', 'tipo', 'cena', 'porque', 'polo'] as $campo) {
                afirmar(isset($cena[$campo]) && $cena[$campo] !== '', $slug . ' cena ' . $i . ': ' . $campo);
            }

            afirmar(in_array($cena['polo'], ['esq', 'dir'], true), $slug . ': polo inválido');
            afirmar(in_array($cena['tipo'], ['filme', 'série'], true), $slug . ': tipo inválido');
        }

        foreach ($guia['citacoes'] as $i => $citacao) {
            foreach (['autor', 'obra', 'ano', 'ideia'] as $campo) {
                afirmar(isset($citacao[$campo]) && $citacao[$campo] !== '', $slug . ' citação ' . $i . ': ' . $campo);
            }
        }
    }
});

teste('slug desconhecido devolve estrutura vazia', function (): void {
    $guia = EixoGuia::porSlug('nao-existe');

    igual('', $guia['resumo']);
    igual([], $guia['cenas']);
    igual([], $guia['citacoes']);
    igual('', $guia['leitura']['esq']);
});
