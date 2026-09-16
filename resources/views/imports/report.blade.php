@extends('layouts.app')

@section('title', 'Reporte BI #' . $import->id . ' - Megalabs Analytics')

@section('content')
<div class="space-y-8">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <a href="{{ route('dashboard') }}" class="p-2.5 rounded-2xl bg-white hover:bg-slate-100 text-slate-600 hover:text-megalabs-800 border border-slate-200 transition shadow-sm" title="Volver al Listado">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-megalabs-700 uppercase tracking-wider">Reporte de Inteligencia de Negocio</span>
                    <span class="text-slate-400">•</span>
                    <span class="text-xs font-mono text-slate-500 font-bold">ID #{{ $import->id }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-megalabs-950 tracking-tight flex items-center">
                    {{ $import->original_name }}
                </h1>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('imports.detail', $import->id) }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-megalabs-50 hover:bg-megalabs-100 text-megalabs-800 border border-megalabs-300 shadow-sm transition flex items-center">
                <i class="fa-solid fa-list-check mr-2 text-megalabs-700"></i> Ver Detalle de Registros
            </a>
            <!-- <a href="/api/reports/summary?import_id={{ $import->id }}" target="_blank" class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-sm transition flex items-center">
                <i class="fa-solid fa-code mr-1.5 text-megalabs-600"></i> JSON Report
            </a>
            <button type="button" onclick="deleteImport({{ $import->id }})" class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 shadow-sm transition flex items-center">
                <i class="fa-solid fa-trash-can mr-1.5"></i> Eliminar
            </button> -->
        </div>
    </div>

    <!-- Import Overview Card -->
    <div class="white-card rounded-3xl p-6 shadow-sm border border-slate-200">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Status -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">Estado</span>
                @if($import->status === 'completed')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Completado
                    </span>
                @elseif($import->status === 'processing')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                        <i class="fa-solid fa-spinner fa-spin mr-1 text-amber-600"></i> Procesando
                    </span>
                @elseif($import->status === 'failed')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                        <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i> Fallido
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                        <i class="fa-solid fa-clock mr-1 text-slate-500"></i> Pendiente
                    </span>
                @endif
            </div>

            <!-- Total Ingested -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">Total Filas CSV</span>
                <span class="text-xl font-bold text-slate-900 font-mono">{{ number_format($import->total_rows) }}</span>
            </div>

            <!-- Success Rows -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-emerald-700 block mb-1">Registros Válidos</span>
                <span class="text-xl font-bold text-emerald-700 font-mono">{{ number_format($import->successful_rows) }}</span>
            </div>

            <!-- Failed Rows -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-rose-700 block mb-1">Inconsistencias</span>
                <span class="text-xl font-bold text-rose-700 font-mono">{{ number_format($import->failed_rows) }}</span>
            </div>

            <!-- Total Revenue -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-megalabs-800 block mb-1">Ingresos Calculados</span>
                <span class="text-xl font-bold text-megalabs-900 font-mono">${{ number_format($import->total_revenue, 2) }}</span>
            </div>

            <!-- Processing Time -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-500 block mb-1">Duración del cargue</span>
                <span class="text-sm font-bold text-slate-700 font-mono">
                    @if($import->started_at && $import->completed_at)
                        {{ $import->started_at->diffInSeconds($import->completed_at) }} segundos
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- BI Visual Analytics & Charts Section (Light Mode) -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-megalabs-900 flex items-center">
                <i class="fa-solid fa-chart-pie text-megalabs-600 mr-2.5"></i> Análisis Consolidado de Negocio
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart 1: Top 5 Products -->
            <div class="white-card rounded-3xl p-6 shadow-sm border border-slate-200 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-megalabs-900 flex items-center">
                        <i class="fa-solid fa-trophy text-amber-500 mr-2"></i> Top 5 Productos por Ingresos
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Mayores generadores de ventas</span>
                </div>
                <div class="relative h-72">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Category Distribution -->
            <div class="white-card rounded-3xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-megalabs-900 flex items-center">
                        <i class="fa-solid fa-layer-group text-megalabs-600 mr-2"></i> Distribución por Categoría
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Proporción %</span>
                </div>
                <div class="relative h-72 flex items-center justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Geographical Distribution Chart & Country Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart 3: Geographical Distribution -->
            <div class="white-card rounded-3xl p-6 shadow-sm border border-slate-200 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-megalabs-900 flex items-center">
                        <i class="fa-solid fa-earth-americas text-emerald-600 mr-2"></i> Distribución Geográfica por País
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Ingresos agrupados por país</span>
                </div>
                <div class="relative h-72">
                    <canvas id="countryChart"></canvas>
                </div>
            </div>

            <!-- Summary KPI Breakdown -->
            <div class="white-card rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-megalabs-900 flex items-center mb-4">
                        <i class="fa-solid fa-receipt text-megalabs-600 mr-2"></i> Métricas Operativas
                    </h3>
                    
                    <div class="space-y-3.5">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-600">Ticket Promedio (AOV)</span>
                            <span class="text-base font-bold text-megalabs-900 font-mono">${{ number_format($report['summary']['average_order_value'] ?? 0, 2) }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-600">Unidades Totales Vendidas</span>
                            <span class="text-base font-bold text-megalabs-900 font-mono">{{ number_format($report['summary']['total_units_sold'] ?? 0) }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-600">Transacciones Válidas</span>
                            <span class="text-base font-bold text-megalabs-900 font-mono">{{ number_format($report['summary']['total_transactions'] ?? 0) }}</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-600">Rango de Fechas</span>
                            <span class="text-xs font-bold text-slate-800">
                                {{ $report['summary']['date_range']['start'] ?? '-' }} a {{ $report['summary']['date_range']['end'] ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 text-xs text-slate-500 font-medium flex items-center justify-between">
                    <span>Tasa de Integridad:</span>
                    <span class="font-bold font-mono {{ $import->failed_rows == 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $import->total_rows > 0 ? round(($import->successful_rows / $import->total_rows) * 100, 2) : 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Delete action
    async function deleteImport(id) {
        if (!confirm('¿Estás seguro de eliminar esta importación?')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const res = await fetch(`/api/imports/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.href = "{{ route('dashboard') }}", 1000);
            } else {
                showToast(data.message || 'Error al eliminar', 'error');
            }
        } catch (e) {
            showToast('Error de red al eliminar', 'error');
        }
    }

    // Chart.js Visualizations Setup (Megalabs Light Theme)
    const reportData = {!! json_encode($report, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: '{}' !!};

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Top 5 Products Chart (Megalabs Emerald)
        const topProducts = reportData.top_products || [];
        const topProductLabels = topProducts.map(p => p.product_name.length > 25 ? p.product_name.substring(0, 25) + '...' : p.product_name);
        const topProductRevenues = topProducts.map(p => p.total_revenue);

        const ctxProducts = document.getElementById('topProductsChart').getContext('2d');
        new Chart(ctxProducts, {
            type: 'bar',
            data: {
                labels: topProductLabels,
                datasets: [{
                    label: 'Ingresos ($ USD)',
                    data: topProductRevenues,
                    backgroundColor: 'rgba(0, 135, 90, 0.85)',
                    borderColor: '#00875A',
                    borderWidth: 1.5,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                return `$${Number(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: { color: '#475569', font: { size: 11, weight: '600' } }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: {
                            color: '#475569',
                            font: { size: 11, weight: '600' },
                            callback: function(value) { return '$' + Number(value).toLocaleString(); }
                        }
                    }
                }
            }
        });

        // 2. Category Distribution Chart (Doughnut - Megalabs shades)
        const categories = reportData.category_distribution || [];
        const categoryLabels = categories.map(c => c.category);
        const categoryRevenues = categories.map(c => c.total_revenue);

        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryRevenues,
                    backgroundColor: [
                        '#00875A', '#059669', '#10b981', '#34d399', '#047857', '#00704A', '#6ee7b7'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#334155', boxWidth: 12, padding: 14, font: { size: 11, weight: '600' } }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const val = ctx.raw;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return `${ctx.label}: $${Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 })} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // 3. Country Distribution Chart (Horizontal Bar - Teal/Emerald)
        const countries = reportData.geographical_distribution || [];
        const countryLabels = countries.map(c => c.country);
        const countryRevenues = countries.map(c => c.total_revenue);

        const ctxCountry = document.getElementById('countryChart').getContext('2d');
        new Chart(ctxCountry, {
            type: 'bar',
            data: {
                labels: countryLabels,
                datasets: [{
                    label: 'Ingresos por País ($)',
                    data: countryRevenues,
                    backgroundColor: 'rgba(5, 150, 105, 0.85)',
                    borderColor: '#059669',
                    borderWidth: 1.5,
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                return `$${Number(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: {
                            color: '#475569',
                            font: { size: 11, weight: '600' },
                            callback: function(value) { return '$' + Number(value).toLocaleString(); }
                        }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: { color: '#475569', font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    });
</script>
@endpush
