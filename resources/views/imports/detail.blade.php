@extends('layouts.app')

@section('title', 'Detalle de Importación #' . $import->id . ' - Megalabs Analytics')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <a href="{{ route('dashboard') }}" class="p-2.5 rounded-2xl bg-white hover:bg-slate-100 text-slate-600 hover:text-megalabs-800 border border-slate-200 transition shadow-sm" title="Volver al Listado Principal">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-megalabs-700 uppercase tracking-wider">Detalle del cargue</span>
                    <span class="text-slate-400">•</span>
                    <span class="text-xs font-mono text-slate-500 font-bold">ID #{{ $import->id }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-megalabs-950 tracking-tight flex items-center">
                    {{ $import->original_name }}
                </h1>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('imports.report', $import->id) }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-gradient-to-r from-megalabs-700 to-megalabs-600 hover:from-megalabs-800 hover:to-megalabs-700 text-white shadow-md shadow-megalabs-700/20 transition flex items-center">
                <i class="fa-solid fa-chart-pie mr-2"></i> Ver Reporte Gráfico
            </a>
            <!-- <a href="/api/reports/summary?import_id={{ $import->id }}" target="_blank" class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-sm transition flex items-center" title="Descargar JSON">
                <i class="fa-solid fa-code mr-1.5 text-megalabs-600"></i> JSON
            </a> -->
        </div>
    </div>

    <!-- Batch Summary Metrics Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="white-card rounded-3xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-500 block mb-1">Total Filas Archivo</span>
            <span class="text-xl font-bold text-slate-900 font-mono">{{ number_format($import->total_rows) }}</span>
        </div>
        <div class="white-card rounded-3xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-emerald-700 block mb-1">Registros Válidos</span>
            <span class="text-xl font-bold text-emerald-700 font-mono">{{ number_format($import->successful_rows) }}</span>
        </div>
        <div class="white-card rounded-3xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-rose-700 block mb-1">Inconsistencias</span>
            <span class="text-xl font-bold text-rose-700 font-mono">{{ number_format($import->failed_rows) }}</span>
        </div>
        <div class="white-card rounded-3xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-megalabs-800 block mb-1">Facturación del Lote</span>
            <span class="text-xl font-bold text-megalabs-900 font-mono">${{ number_format($import->total_revenue, 2) }}</span>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="white-card rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
        
        <!-- Tab Navigation Buttons -->
        <div class="flex items-center space-x-3 border-b border-slate-200 pb-4 mb-6">
            <button type="button" id="tab-btn-valid" onclick="switchTab('valid')" class="px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 {{ $activeTab === 'valid' ? 'bg-megalabs-50 text-megalabs-800 border border-megalabs-300 shadow-sm' : 'text-slate-600 hover:text-megalabs-800 hover:bg-slate-100' }}">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Registros Válidos</span>
                <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-100 text-emerald-800">
                    {{ number_format($import->successful_rows) }}
                </span>
            </button>

            <button type="button" id="tab-btn-errors" onclick="switchTab('errors')" class="px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 {{ $activeTab === 'errors' ? 'bg-rose-50 text-rose-800 border border-rose-300 shadow-sm' : 'text-slate-600 hover:text-rose-800 hover:bg-slate-100' }}">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Inconsistencias Detectadas</span>
                <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-rose-100 text-rose-800">
                    {{ number_format($import->failed_rows) }}
                </span>
            </button>
        </div>

        <!-- ================= TAB 1: REGISTROS VÁLIDOS ================= -->
        <div id="tab-pane-valid" class="{{ $activeTab === 'valid' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- Filters Form (Light) -->
            <form method="GET" action="{{ route('imports.detail', $import->id) }}" class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4 shadow-sm">
                <input type="hidden" name="tab" value="valid">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Filter: Customer -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Buscar por Cliente</label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3 top-3 text-xs text-slate-400"></i>
                            <input type="text" name="customer" value="{{ request('customer') }}" placeholder="Nombre o ID cliente..." class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-megalabs-600 focus:ring-1 focus:ring-megalabs-600">
                        </div>
                    </div>

                    <!-- Filter: Product -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Buscar por Producto</label>
                        <div class="relative">
                            <i class="fa-solid fa-box absolute left-3 top-3 text-xs text-slate-400"></i>
                            <input type="text" name="product" value="{{ request('product') }}" placeholder="Nombre o ID producto..." class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-megalabs-600 focus:ring-1 focus:ring-megalabs-600">
                        </div>
                    </div>

                    <!-- Filter: Category -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Categoría</label>
                        <select name="category" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-megalabs-600 focus:ring-1 focus:ring-megalabs-600 font-medium">
                            <option value="">Todas las Categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ordenar Por</label>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="sort_by" class="bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-megalabs-600 font-medium">
                                <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>ID</option>
                                <option value="date" {{ request('sort_by') === 'date' ? 'selected' : '' }}>Fecha</option>
                                <option value="total_amount" {{ request('sort_by') === 'total_amount' ? 'selected' : '' }}>Total ($)</option>
                                <option value="quantity" {{ request('sort_by') === 'quantity' ? 'selected' : '' }}>Cantidad</option>
                                <option value="unit_price" {{ request('sort_by') === 'unit_price' ? 'selected' : '' }}>Precio Unit.</option>
                            </select>
                            <select name="sort_dir" class="bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs text-slate-800 focus:outline-none focus:border-megalabs-600 font-medium">
                                <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>Ascendente</option>
                                <option value="desc" {{ request('sort_dir', 'desc') === 'desc' ? 'selected' : '' }}>Descendente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-1 border-t border-slate-200">
                    <a href="{{ route('imports.detail', ['id' => $import->id, 'tab' => 'valid']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 transition">
                        Limpiar Filtros
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-megalabs-700 hover:bg-megalabs-800 text-white shadow-sm transition flex items-center">
                        <i class="fa-solid fa-filter mr-1.5"></i> Aplicar Filtros
                    </button>
                </div>
            </form>

            <!-- Table: Valid Records (Light) -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-megalabs-900 uppercase font-bold tracking-wider">
                            <th class="py-3.5 px-3 w-28">Order ID</th>
                            <th class="py-3.5 px-3 w-24">Fecha</th>
                            <th class="py-3.5 px-3">Cliente</th>
                            <th class="py-3.5 px-3">Producto</th>
                            <th class="py-3.5 px-3">Categoría</th>
                            <th class="py-3.5 px-3 text-right">Cant.</th>
                            <th class="py-3.5 px-3 text-right">Precio Unit.</th>
                            <th class="py-3.5 px-3 text-right">Desc.</th>
                            <th class="py-3.5 px-3 text-right text-megalabs-800 font-bold">Total ($)</th>
                            <th class="py-3.5 px-3 text-center">País</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($records as $rec)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-3 font-mono font-bold text-slate-700">
                                    {{ $rec->order_id }}
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-500 font-medium">
                                    {{ $rec->date ? $rec->date->format('Y-m-d') : '-' }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $rec->customer_name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $rec->customer_id }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800">{{ $rec->product_name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $rec->product_id }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-megalabs-50 text-megalabs-800 border border-megalabs-200">
                                        {{ $rec->category }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700 font-medium">
                                    {{ number_format($rec->quantity) }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">
                                    ${{ number_format($rec->unit_price, 2) }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-500">
                                    {{ round($rec->discount * 100, 1) }}%
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-megalabs-900">
                                    ${{ number_format($rec->total_amount, 2) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $rec->country }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-magnifying-glass text-2xl mb-2 text-slate-400 block"></i>
                                    No se encontraron registros que coincidan con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="pt-2">
                    {{ $records->appends(['tab' => 'valid'])->links() }}
                </div>
            @endif

        </div>

        <!-- ================= TAB 2: INCONSISTENCIAS ================= -->
        <div id="tab-pane-errors" class="{{ $activeTab === 'errors' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- Filters Form for Errors (Light) -->
            <form method="GET" action="{{ route('imports.detail', $import->id) }}" class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
                <input type="hidden" name="tab" value="errors">

                <div class="w-full sm:max-w-md relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-xs text-slate-400"></i>
                    <input type="text" name="error_search" value="{{ request('error_search') }}" placeholder="Buscar por motivo de error o contenido..." class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-600 focus:ring-1 focus:ring-rose-500">
                </div>

                <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                    <a href="{{ route('imports.detail', ['id' => $import->id, 'tab' => 'errors']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 transition">
                        Limpiar
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-700 hover:bg-rose-800 text-white shadow-sm transition flex items-center">
                        <i class="fa-solid fa-filter mr-1.5"></i> Filtrar Errores
                    </button>
                </div>
            </form>

            <!-- Table: Inconsistencies (Light) -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-rose-900 uppercase font-bold tracking-wider">
                            <th class="py-3.5 px-4 w-24">Fila CSV</th>
                            <th class="py-3.5 px-4">Motivo del Fallo</th>
                            <th class="py-3.5 px-4">Datos Originales (JSON Raw)</th>
                            <th class="py-3.5 px-4 text-right">Fecha Auditoría</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($errors as $error)
                            <tr class="hover:bg-rose-50/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                                    #{{ $error->row_number }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @foreach(explode(' | ', $error->error_reason) as $subReason)
                                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-800 border border-rose-200 mr-1 mb-1 font-bold">
                                            {{ $subReason }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600 max-w-md truncate">
                                    <code>{{ is_array($error->raw_data) ? json_encode($error->raw_data, JSON_UNESCAPED_UNICODE) : $error->raw_data }}</code>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-500 font-medium">
                                    {{ $error->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-500">
                                    <i class="fa-solid fa-circle-check text-2xl text-emerald-600 mb-2 block"></i>
                                    No se encontraron inconsistencias para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($errors->hasPages())
                <div class="pt-2">
                    {{ $errors->appends(['tab' => 'errors'])->links() }}
                </div>
            @endif

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        const paneValid = document.getElementById('tab-pane-valid');
        const paneErrors = document.getElementById('tab-pane-errors');
        const btnValid = document.getElementById('tab-btn-valid');
        const btnErrors = document.getElementById('tab-btn-errors');

        if (tab === 'valid') {
            paneValid.classList.remove('hidden');
            paneErrors.classList.add('hidden');

            btnValid.className = 'px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 bg-megalabs-50 text-megalabs-800 border border-megalabs-300 shadow-sm';
            btnErrors.className = 'px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100';
        } else {
            paneErrors.classList.remove('hidden');
            paneValid.classList.add('hidden');

            btnErrors.className = 'px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 bg-rose-50 text-rose-800 border border-rose-300 shadow-sm';
            btnValid.className = 'px-4 py-2.5 rounded-2xl font-bold text-sm transition flex items-center space-x-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100';
        }

        // Update URL query parameter without full reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hasErrorsPage = urlParams.has('errors_page');

        if (tabParam === 'errors' || (!tabParam && hasErrorsPage)) {
            switchTab('errors');
        }
    });
</script>
@endpush
