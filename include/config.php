<?php
// Configurações gerais do CineAxes

$nome_cinema = "CineAxes";
$endereco     = "Rua das Estrelas, 123";
$contato      = "(11) 99999-0000";

/**
 * Saudação de acordo com o horário atual.
 */
function saudar(): string
{
    $hora = (int) date('H');
    if ($hora < 12) return "Bom dia";
    if ($hora < 18) return "Boa tarde";
    return "Boa noite";
}

/**
 * Formata um preço em reais.
 */
function moeda(float $valor): string
{
    return "R$ " . number_format($valor, 2, ',', '.');
}

/**
 * Nome amigável do status do filme.
 */
function rotuloStatusFilme(string $status): string
{
    return match ($status) {
        'em_cartaz'      => 'Em cartaz',
        'em_breve'       => 'Em breve',
        'fora_de_cartaz' => 'Fora de cartaz',
        default          => ucfirst($status),
    };
}
