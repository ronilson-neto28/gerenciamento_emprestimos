@extends('layouts.admin')

@section('title', 'Dashboard Admin')
{{-- Zera os títulos do layout pai para não duplicar na tela --}}
@section('heading', '')
@section('subheading', '')

@section('content')


    <?php
        /* ---------- Verificação e Carregamento de CSS/Vite ---------- */
        $viteReady = (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')));
        $dashCssUrl = !$viteReady ? route('assets.admin.dashboard_css', [], false) : '';
    ?>
    @push('styles')
        @if ($viteReady)
            @vite(['resources/css/admin/dashboard.css'])
        @else
            <link rel="stylesheet" href="<?php echo $dashCssUrl; ?>">
        @endif
    @endpush

    @php
        /* ---------- FALLBACKS DEFENSIVOS DE TODAS AS VARIÁVEIS DO CONTROLLER ---------- */
        $todayLabel          = isset($todayLabel) ? $todayLabel : date('d/m/Y');
        $dueTodayLoans       = (isset($dueTodayLoans) && is_iterable($dueTodayLoans)) ? $dueTodayLoans : [];
        $overdueInstallments = (isset($overdueInstallments) && is_iterable($overdueInstallments)) ? $overdueInstallments : [];
        $monthlyRevenue      = (isset($monthlyRevenue) && is_array($monthlyRevenue)) ? $monthlyRevenue : [];
        $monthlyInvested     = (isset($monthlyInvested) && is_array($monthlyInvested)) ? $monthlyInvested : [];

        /* ---------- Mapeamento seguro dos 8 KPIs ---------- */
        $kpi_clientes_default   = array('label' => 'Clientes', 'value' => '0', 'trend' => 'CADASTROS');
        $kpi_emp_default        = array('label' => 'Empréstimos', 'value' => '0', 'trend' => 'CONTRATOS');
        $kpi_ent_default        = array('label' => 'Entradas', 'value' => 'R$ 0,00', 'trend' => 'NESTE MÊS');
        $kpi_sai_default        = array('label' => 'Saídas', 'value' => 'R$ 0,00', 'trend' => 'NESTE MÊS');
        $kpi_res_default        = array('label' => 'Resultado Recebido', 'value' => 'R$ 0,00', 'trend' => 'RECEBIDO');
        $kpi_jur_default        = array('label' => 'Juros Recebidos', 'value' => 'R$ 0,00', 'trend' => 'LUCRO JUROS');
        $kpi_mul_default        = array('label' => 'Multas Recebidas', 'value' => 'R$ 0,00', 'trend' => 'MORA/MULTA');
        $kpi_abe_default        = array('label' => 'Total em Aberto', 'value' => 'R$ 0,00', 'trend' => 'SALDO A RECEBER');

        if (isset($stats) && is_array($stats)) {
            $kpi_clientes    = isset($stats[0]) && is_array($stats[0]) ? $stats[0] : $kpi_clientes_default;
            $kpi_emprestimos = isset($stats[1]) && is_array($stats[1]) ? $stats[1] : $kpi_emp_default;
            $kpi_entradas    = isset($stats[2]) && is_array($stats[2]) ? $stats[2] : $kpi_ent_default;
            $kpi_saidas      = isset($stats[3]) && is_array($stats[3]) ? $stats[3] : $kpi_sai_default;
            $kpi_resultado   = isset($stats[4]) && is_array($stats[4]) ? $stats[4] : $kpi_res_default;
            $kpi_juros       = isset($stats[5]) && is_array($stats[5]) ? $stats[5] : $kpi_jur_default;
            $kpi_multas      = isset($stats[6]) && is_array($stats[6]) ? $stats[6] : $kpi_mul_default;
            $kpi_aberto      = isset($stats[7]) && is_array($stats[7]) ? $stats[7] : $kpi_abe_default;
        } else {
            $kpi_clientes    = $kpi_clientes_default;
            $kpi_emprestimos = $kpi_emp_default;
            $kpi_entradas    = $kpi_ent_default;
            $kpi_saidas      = $kpi_sai_default;
            $kpi_resultado   = $kpi_res_default;
            $kpi_juros       = $kpi_jur_default;
            $kpi_multas      = $kpi_mul_default;
            $kpi_aberto      = $kpi_abe_default;
        }
        unset($kpi_clientes_default, $kpi_emp_default, $kpi_ent_default, $kpi_sai_default, $kpi_res_default, $kpi_jur_default, $kpi_mul_default, $kpi_abe_default);

        /* ---------- Resumo do Dia (miniStats) ---------- */
        $dia_parcelas_default = '0 parcelas';
        $dia_hoje_default     = 'R$ 0,00';
        $dia_update_default   = 'Há 15 minutos';

        if (isset($miniStats) && is_array($miniStats)) {
            $dia_parcelas = (isset($miniStats[0]) && is_array($miniStats[0]) && isset($miniStats[0]['value'])) ? $miniStats[0]['value'] : $dia_parcelas_default;
            $dia_hoje     = (isset($miniStats[1]) && is_array($miniStats[1]) && isset($miniStats[1]['value'])) ? $miniStats[1]['value'] : $dia_hoje_default;
            $dia_update   = (isset($miniStats[2]) && is_array($miniStats[2]) && isset($miniStats[2]['value'])) ? $miniStats[2]['value'] : $dia_update_default;
        } else {
            $dia_parcelas = $dia_parcelas_default;
            $dia_hoje     = $dia_hoje_default;
            $dia_update   = $dia_update_default;
        }
        unset($dia_parcelas_default, $dia_hoje_default, $dia_update_default);

        /* ---------- Ícones SVG inline ---------- */
        $svg_ico_users = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M9 11a4 4 0 100-8 4 4 0 000 8zm8 1a3 3 0 100-6 3 3 0 000 6zM3 20c0-3.3 2.7-6 6-6h0c3.3 0 6 2.7 6 6M17 15c2.8 0 5 2.2 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $svg_ico_cal   = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><rect x="3.5" y="4.5" width="17" height="17" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v3M16 3v3M3.5 10H20.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
        $svg_ico_in    = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M7 17l10-10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7h8v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $svg_ico_out   = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M17 17L7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 7H7v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $svg_ico_check = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M5 12.5l4 4 10-10" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $svg_ico_dolar = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M12 3v18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 7.5C17 6 15.5 5 13.5 5H12c-2.8 0-4.5 1.8-4.5 4S9 12.5 12 12.5h.5c2.5 0 4.5 1.6 4.5 3.8S13.8 20 11.5 20c-1.8 0-3.3-.9-4-2.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $svg_ico_warn  = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><path d="M12 3.5L21 19H3L12 3.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v5M12 18h.01" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>';
        $svg_ico_clock = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dash-ico-svg"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    @endphp

    <?php
    if (!function_exists('dashGetInitials')) {
        function dashGetInitials($nomeRaw)
        {
            $nomeStr = is_string($nomeRaw) ? $nomeRaw : (string)$nomeRaw;
            $nomeTrim = trim($nomeStr);
            if ($nomeTrim === '') return 'II';
            $nomeNorm = preg_replace('/\s+/u', ' ', $nomeTrim);
            if ($nomeNorm === '' || $nomeNorm === null) return 'II';
            $parts = explode(' ', $nomeNorm, 2);
            $first = $parts[0];
            $second = count($parts) > 1 ? $parts[1] : '';
            $letter1 = (is_string($first) && $first !== '') ? mb_substr($first, 0, 1) : 'I';
            $letter2 = (is_string($second) && $second !== '') ? mb_substr($second, 0, 1) : '';
            return mb_strtoupper(($letter1 ?: 'I') . $letter2);
        }
    }
    ?>

    {{-- Encapsulamento principal para isolar e aplicar o CSS Light Mode --}}
    <div class="page-admin-dashboard">

        {{-- 1) CABEÇALHO DA PÁGINA --}}
        <div class="dash-header">
            <div class="dash-header-copy">
                <span class="dash-badge dash-badge-hero">
                    <span class="dash-badge-dot"></span>
                    VISÃO GERAL DA OPERAÇÃO
                </span>
                <h1 class="dash-title">Dashboard</h1>
                <p class="dash-subtitle">
                    Resumo em tempo real de clientes, carteira de empréstimos, arrecadação e alertas do dia.
                </p>
            </div>
            <a href="{{ route('admin.financeiro') }}" class="dash-btn-financeiro">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true">
                    <path d="M12 3v18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M17 7.5C17 6 15.5 5 13.5 5H12C9.2 5 7.5 6.8 7.5 9s1.7 4 4.5 4H12c2.5 0 4.5 1.6 4.5 3.8S13.8 20 11.5 20C9.7 20 8.2 19.1 7.5 17.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Módulo Financeiro</span>
            </a>
        </div>

        {{-- 2) GRID DE 8 KPIs (Linha 1) --}}
        <section class="dash-kpis dash-kpis-row" aria-label="Métricas principais">
            <article class="dash-kpi dash-kpi-1">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-1">{!! $svg_ico_users !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-1">CADASTROS</span>
                </div>
                <div class="dash-kpi-label">CLIENTES</div>
                <div class="dash-kpi-value">{{ $kpi_clientes['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-2">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-2">{!! $svg_ico_cal !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-2">CONTRATOS</span>
                </div>
                <div class="dash-kpi-label">EMPRÉSTIMOS</div>
                <div class="dash-kpi-value">{{ $kpi_emprestimos['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-3">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-3">{!! $svg_ico_in !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-3">NESTE MÊS</span>
                </div>
                <div class="dash-kpi-label">ENTRADAS</div>
                <div class="dash-kpi-value dash-kpi-value-green">{{ $kpi_entradas['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-4">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-4">{!! $svg_ico_out !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-4">NESTE MÊS</span>
                </div>
                <div class="dash-kpi-label">SAÍDAS</div>
                <div class="dash-kpi-value">{{ $kpi_saidas['value'] }}</div>
            </article>
        </section>

        {{-- GRID DE 8 KPIs (Linha 2) --}}
        <section class="dash-kpis dash-kpis-row" aria-label="Métricas de resultado">
            <article class="dash-kpi dash-kpi-5">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-5">{!! $svg_ico_check !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-5">RECEBIDO</span>
                </div>
                <div class="dash-kpi-label">RESULTADO RECEBIDO</div>
                <div class="dash-kpi-value dash-kpi-value-green">{{ $kpi_resultado['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-6">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-6">{!! $svg_ico_dolar !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-6">LUCRO JUROS</span>
                </div>
                <div class="dash-kpi-label">JUROS RECEBIDOS</div>
                <div class="dash-kpi-value dash-kpi-value-indigo">{{ $kpi_juros['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-7">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-7">{!! $svg_ico_warn !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-7">MORA/MULTA</span>
                </div>
                <div class="dash-kpi-label">MULTAS RECEBIDAS</div>
                <div class="dash-kpi-value dash-kpi-value-amber">{{ $kpi_multas['value'] }}</div>
            </article>

            <article class="dash-kpi dash-kpi-8">
                <div class="dash-kpi-toprow">
                    <span class="dash-kpi-ico dash-kpi-ico-8">{!! $svg_ico_clock !!}</span>
                    <span class="dash-kpi-pill dash-kpi-pill-8">SALDO A RECEBER</span>
                </div>
                <div class="dash-kpi-label">TOTAL EM ABERTO</div>
                <div class="dash-kpi-value dash-kpi-value-sky">{{ $kpi_aberto['value'] }}</div>
            </article>
        </section>

        {{-- 3) LINHA HERO (Ações rápidas & Resumo do Dia) --}}
        <section class="dash-hero" aria-label="Ações rápidas e resumo do dia">
            <article class="dash-card dash-hero-main">
                <div class="dash-hero-badge">CENTRAL DE CONTROLE</div>
                <h2 class="dash-hero-title">Bem-vindo ao Painel Administrativo</h2>
                <p class="dash-hero-text">
                    Acompanhe indicadores em tempo real, gerencie cobranças diárias e acesse os atalhos dos módulos operacionais do sistema.
                </p>
                <div class="dash-hero-actions">
                    <a href="{{ route('admin.emprestimos') }}" class="dash-btn dash-btn-primary">
                        Acessar Empréstimos
                    </a>
                    <a href="{{ route('admin.clientes') }}" class="dash-btn dash-btn-secondary">
                        Ver Clientes
                    </a>
                </div>
            </article>

            <aside class="dash-card dash-hero-resume">
                <h3 class="dash-resume-title">Resumo do Dia</h3>

                <div class="dash-resume-row">
                    <div class="dash-resume-row-label">Total de parcelas pagas</div>
                    <div class="dash-resume-row-value">{{ $dia_parcelas }}</div>
                </div>
                <div class="dash-resume-divider"></div>

                <div class="dash-resume-row">
                    <div class="dash-resume-row-label">Dinheiro arrecadado hoje</div>
                    <div class="dash-resume-row-value dash-resume-row-value-green">{{ $dia_hoje }}</div>
                </div>
                <div class="dash-resume-divider"></div>

                <div class="dash-resume-row dash-resume-row-live">
                    <div class="dash-resume-row-label">Última atualização</div>
                    <span class="dash-live-pill">
                        <span class="dash-live-dot"></span>
                        {{ $dia_update }}
                    </span>
                </div>
            </aside>
        </section>

        {{-- 4) LINHA ANÁLISE & ALERTAS OPERACIONAIS --}}
        <section class="dash-bottom" aria-label="Análise mensal e alertas">
            {{-- Coluna ESQUERDA: Gráficos --}}
            <div class="dash-col-charts">
                <?php
                    $revenueAmounts  = !empty($monthlyRevenue) ? array_column($monthlyRevenue, 'amount') : [0];
                    $investedAmounts = !empty($monthlyInvested) ? array_column($monthlyInvested, 'amount') : [0];
                    $maxRev = max(1, ...$revenueAmounts);
                    $maxInv = max(1, ...$investedAmounts);
                    $highlightMonth = null;
                    if (!empty($monthlyInvested)) {
                        $maxInvMonth = collect($monthlyInvested)->sortByDesc('amount')->first();
                        if (is_array($maxInvMonth) && isset($maxInvMonth['month'])) {
                            $highlightMonth = $maxInvMonth['month'];
                        }
                    }
                ?>

                {{-- Card 1: Dinheiro Arrecadado --}}
                <article class="dash-card dash-chart">
                    <div class="dash-chart-head">
                        <div>
                            <h2 class="dash-card-title">Dinheiro Arrecadado</h2>
                            <p class="dash-card-subtitle">Total recebido em cada mês, de janeiro a dezembro.</p>
                        </div>
                        <span class="dash-chart-pill">12 meses</span>
                    </div>

                    <div class="dash-chart-wrap dash-chart-wrap-rev">
                        @foreach ($monthlyRevenue as $revItem)
                            <?php
                                $revAmount = (isset($revItem['amount']) && is_numeric($revItem['amount'])) ? (float)$revItem['amount'] : 0;
                                $revMonth  = isset($revItem['month']) ? $revItem['month'] : '';
                                $h = max(10, (int) round(($revAmount / $maxRev) * 120));
                            ?>
                            <div class="dash-chart-col">
                                <div class="dash-chart-bar dash-chart-bar-rev" style="--ch: {{ $h }}px;"></div>
                                <div class="dash-chart-lab">{{ $revMonth }}</div>
                            </div>
                        @endforeach
                    </div>
                </article>

                {{-- Card 2: Dinheiro Investido --}}
                <article class="dash-card dash-chart">
                    <div class="dash-chart-head">
                        <div>
                            <h2 class="dash-card-title">Dinheiro Investido</h2>
                            <p class="dash-card-subtitle">Total emprestado em cada mês, de janeiro a dezembro.</p>
                        </div>
                        <span class="dash-chart-pill">12 meses</span>
                    </div>

                    <div class="dash-chart-wrap dash-chart-wrap-inv">
                        @foreach ($monthlyInvested as $invItem)
                            <?php
                                $invAmount = (isset($invItem['amount']) && is_numeric($invItem['amount'])) ? (float)$invItem['amount'] : 0;
                                $invMonth  = isset($invItem['month']) ? $invItem['month'] : '';
                                $h = max(10, (int) round(($invAmount / $maxInv) * 160));
                                $isH = ($highlightMonth === $invMonth && $invAmount > 0);
                            ?>
                            <div class="dash-chart-col <?php echo $isH ? 'dash-chart-col-hot' : ''; ?>">
                                <div class="dash-chart-bar dash-chart-bar-inv <?php echo $isH ? 'dash-chart-bar-inv-hot' : ''; ?>" style="--ch: {{ $h }}px;"></div>
                                <div class="dash-chart-lab">{{ $invMonth }}</div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            {{-- Coluna DIREITA: Alertas --}}
            <div class="dash-col-alerts">
                {{-- Card 1: Vencendo Hoje --}}
                <article class="dash-card dash-alert dash-alert-due">
                    <div class="dash-alert-head">
                        <span class="dash-alert-badge dash-alert-badge-due">
                            <span class="dash-alert-dot dash-alert-dot-due"></span>
                            Vencendo Hoje
                        </span>
                        <div class="dash-alert-date">{{ $todayLabel }}</div>
                    </div>
                    <p class="dash-alert-sub">Empréstimos com parcelas a vencer na data atual.</p>

                    <div class="dash-alert-list">
                        @forelse ($dueTodayLoans as $loan)
                            <div class="dash-alert-item">
                                <div class="dash-avatar">
                                    <span><?php echo dashGetInitials(isset($loan['cliente']) ? $loan['cliente'] : 'Cliente'); ?></span>
                                </div>
                                <div class="dash-alert-item-copy">
                                    <div class="dash-alert-item-title"><?php echo isset($loan['cliente']) ? e($loan['cliente']) : 'Cliente'; ?></div>
                                    <div class="dash-alert-item-meta">
                                        <?php echo isset($loan['parcela']) ? e($loan['parcela']) : 'Parcela 01/01'; ?> • <?php echo isset($loan['valor']) ? e($loan['valor']) : 'R$ 0,00'; ?>
                                    </div>
                                </div>
                                <a href="{{ route('admin.emprestimos') }}" class="dash-btn-darbaixa">Dar Baixa</a>
                            </div>
                        @empty
                            <div class="dash-alert-item dash-alert-item-empty">
                                <div class="dash-avatar dash-avatar-muted"><span>✓</span></div>
                                <div class="dash-alert-item-copy">
                                    <div class="dash-alert-item-title">Nenhuma parcela vence hoje</div>
                                    <div class="dash-alert-item-meta">Não há parcelas previstas para {{ $todayLabel }}.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </article>

                {{-- Card 2: Parcelas Vencidas --}}
                <article class="dash-card dash-alert dash-alert-overdue">
                    <div class="dash-alert-head">
                        <span class="dash-alert-badge dash-alert-badge-overdue">
                            <span class="dash-alert-dot dash-alert-dot-overdue"></span>
                            Parcelas Vencidas
                        </span>
                    </div>
                    <p class="dash-alert-sub">Parcelas que já passaram do vencimento e precisam de acompanhamento.</p>

                    @php $temVencidas = count($overdueInstallments) > 0; @endphp
                    @if ($temVencidas)
                        <div class="dash-alert-list">
                            @foreach ($overdueInstallments as $installment)
                                <div class="dash-alert-item">
                                    <div class="dash-avatar dash-avatar-rose">
                                        <span><?php echo dashGetInitials(isset($installment['cliente']) ? $installment['cliente'] : 'Cliente'); ?></span>
                                    </div>
                                    <div class="dash-alert-item-copy">
                                        <div class="dash-alert-item-title"><?php echo isset($installment['cliente']) ? e($installment['cliente']) : 'Cliente'; ?></div>
                                        <div class="dash-alert-item-meta">
                                            <?php echo isset($installment['parcela']) ? e($installment['parcela']) : 'Parcela 01/01'; ?> • <?php echo isset($installment['valor']) ? e($installment['valor']) : 'R$ 0,00'; ?>
                                            @if (!empty($installment['vencimento']))
                                                • Venceu em {{ $installment['vencimento'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.emprestimos') }}" class="dash-btn-darbaixa dash-btn-darbaixa-rose">Dar Baixa</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="dash-empty">
                            <span class="dash-empty-ico">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="28" height="28" aria-hidden="true">
                                    <path d="M5 12.5l4 4 10-10" stroke="#10b981" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="dash-empty-title">Nenhuma parcela vencida</div>
                            <div class="dash-empty-sub">No momento não há parcelas em atraso na carteira.</div>
                        </div>
                    @endif
                </article>
            </div>
        </section>

    </div>
@endsection