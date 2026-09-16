@extends('layouts.app')

@section('title', 'Megalabs Analytics - Gestión de Importaciones')

@section('content')
<div class="space-y-8">

    <!-- Top Hero Banner (Megalabs Light Style) -->
    <div class="white-card rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-sm border border-slate-200">
        <div class="absolute -right-16 -top-16 w-72 h-72 bg-megalabs-100/50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-72 h-72 bg-emerald-100/40 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-megalabs-50 text-megalabs-700 border border-megalabs-200 mb-3">
                    <i class="fa-solid fa-seedling mr-1.5 text-megalabs-600"></i> Plataforma ETL & Business Intelligence
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-megalabs-950 tracking-tight">
                    Gestión Integral de <span class="gradient-text-megalabs">Ventas Masivas</span>
                </h1>
                <p class="mt-2 text-slate-600 text-sm sm:text-base max-w-2xl font-normal leading-relaxed">
                    Carga asíncrona de transacciones retail de alto volumen, auditoría automática de inconsistencias y análisis predictivo consolidado.
                </p>
            </div>

            <!-- Quick Generator Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('samples.generate', ['rows' => 25000, 'errors' => 250]) }}" class="inline-flex items-center px-4 py-2.5 rounded-2xl text-sm font-bold bg-white hover:bg-megalabs-50 text-megalabs-800 border border-slate-200 hover:border-megalabs-300 shadow-sm transition group">
                    <i class="fa-solid fa-download text-megalabs-600 mr-2 group-hover:-translate-y-0.5 transition-transform"></i>
                    Descargar Muestra 25K
                </a>
                <a href="{{ route('samples.generate', ['rows' => 100000, 'errors' => 1000]) }}" class="inline-flex items-center px-4 py-2.5 rounded-2xl text-sm font-bold bg-white hover:bg-megalabs-50 text-megalabs-800 border border-slate-200 hover:border-megalabs-300 shadow-sm transition group">
                    <i class="fa-solid fa-file-zipper text-emerald-600 mr-2 group-hover:-translate-y-0.5 transition-transform"></i>
                    Descargar Muestra 100K
                </a>
            </div>
        </div>
    </div>

    <!-- Global KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Revenue -->
        <div class="white-card rounded-3xl p-5 shadow-sm border border-slate-200 hover:border-megalabs-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Ingresos Totales</span>
                <div class="w-10 h-10 rounded-2xl bg-megalabs-50 border border-megalabs-100 flex items-center justify-center text-megalabs-700">
                    <i class="fa-solid fa-dollar-sign text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-megalabs-900 font-mono">${{ number_format($stats['total_revenue'], 2) }}</h3>
                <p class="text-xs text-slate-500 mt-1 flex items-center font-medium">
                    <i class="fa-solid fa-circle-check text-megalabs-600 mr-1.5"></i> Facturación neta consolidada
                </p>
            </div>
        </div>

        <!-- Total Valid Records -->
        <div class="white-card rounded-3xl p-5 shadow-sm border border-slate-200 hover:border-megalabs-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Ventas Procesadas</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700">
                    <i class="fa-solid fa-database text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-megalabs-900 font-mono">{{ number_format($stats['total_rows_processed']) }}</h3>
                <p class="text-xs text-slate-500 mt-1 flex items-center font-medium">
                    <i class="fa-solid fa-layer-group text-megalabs-600 mr-1.5"></i> Registros válidos guardados
                </p>
            </div>
        </div>

        <!-- Total Imports -->
        <div class="white-card rounded-3xl p-5 shadow-sm border border-slate-200 hover:border-megalabs-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Lotes de Importación</span>
                <div class="w-10 h-10 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-700">
                    <i class="fa-solid fa-file-csv text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline space-x-2">
                    <h3 class="text-2xl font-black text-megalabs-900 font-mono">{{ $stats['total_imports'] }}</h3>
                    <span class="text-xs text-megalabs-700 font-bold">({{ $stats['completed_imports'] }} exitosos)</span>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center font-medium">
                    <i class="fa-solid fa-clock text-teal-600 mr-1.5"></i> {{ $stats['processing_imports'] }} en procesamiento
                </p>
            </div>
        </div>

        <!-- Total Errors Detected -->
        <div class="white-card rounded-3xl p-5 shadow-sm border border-slate-200 hover:border-rose-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Inconsistencias</span>
                <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black text-rose-700 font-mono">{{ number_format($stats['total_errors']) }}</h3>
                <p class="text-xs text-slate-500 mt-1 flex items-center font-medium">
                    <i class="fa-solid fa-filter-circle-xmark text-rose-600 mr-1.5"></i> Filas aisladas por control
                </p>
            </div>
        </div>
    </div>

    <!-- Upload CSV Section (Drag & Drop - Light) -->
    <div class="white-card rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-megalabs-900 flex items-center">
                    <i class="fa-solid fa-cloud-arrow-up text-megalabs-600 mr-2.5"></i> Cargar Nuevo Archivo de Ventas (CSV)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5 font-normal">El archivo será procesado mediante nuestro pipeline de alto rendimiento sin bloquear el navegador.</p>
            </div>
            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200 inline-flex items-center w-fit">
                <i class="fa-solid fa-shield-halved text-megalabs-600 mr-2"></i> Límite: 100MB
            </span>
        </div>

        <form id="upload-form" action="{{ route('api.imports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <!-- Drag and Drop Dropzone -->
            <div id="dropzone" class="border-2 border-dashed border-megalabs-200 hover:border-megalabs-500 rounded-2xl p-8 text-center transition-all bg-slate-50/70 hover:bg-megalabs-50/40 cursor-pointer relative group">
                <input type="file" id="file" name="file" accept=".csv" class="hidden" required>
                
                <div id="dropzone-default" class="space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-megalabs-50 border border-megalabs-200 flex items-center justify-center text-megalabs-600 group-hover:scale-110 transition-transform shadow-sm">
                        <i class="fa-solid fa-file-csv text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-megalabs-950">
                            Arrastra y suelta tu archivo CSV aquí, o <span class="text-megalabs-700 underline underline-offset-4 group-hover:text-megalabs-800">explora tus archivos</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Compatible con separadores de coma (,), punto y coma (;) o tabulador.</p>
                    </div>
                </div>

                <div id="dropzone-selected" class="hidden space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-700">
                        <i class="fa-solid fa-circle-check text-3xl"></i>
                    </div>
                    <div>
                        <p id="selected-file-name" class="text-sm font-bold text-slate-800"></p>
                        <p id="selected-file-size" class="text-xs text-slate-500 mt-0.5"></p>
                    </div>
                    <button type="button" id="btn-remove-file" class="text-xs text-rose-600 hover:text-rose-800 underline font-semibold">
                        Eliminar archivo
                    </button>
                </div>
            </div>

            <!-- Upload Progress Bar -->
            <div id="upload-progress-container" class="hidden space-y-2">
                <div class="flex justify-between text-xs text-slate-600 font-medium">
                    <span id="upload-status-text" class="flex items-center">
                        <i class="fa-solid fa-spinner fa-spin text-megalabs-600 mr-2"></i> Subiendo y procesando lote...
                    </span>
                    <span id="upload-percentage" class="font-mono text-megalabs-700 font-bold">0%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                    <div id="upload-progress-bar" class="bg-gradient-to-r from-megalabs-700 to-emerald-500 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end pt-2">
                <button type="submit" id="btn-submit-upload" class="px-6 py-3 rounded-2xl font-bold text-sm bg-gradient-to-r from-megalabs-700 to-megalabs-600 hover:from-megalabs-800 hover:to-megalabs-700 text-white shadow-md shadow-megalabs-700/20 transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-upload mr-2"></i> Iniciar Cargue Archivo
                </button>
            </div>
        </form>
    </div>

    <!-- Main Imports Table Section (Light Mode) -->
    <div class="white-card rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-megalabs-900 flex items-center">
                    <i class="fa-solid fa-table-list text-megalabs-600 mr-2.5"></i> Listado de Importaciones
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Historial de archivos procesados. Consulta los registros cargados, inconsistencias o reportes analíticos.</p>
            </div>

            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-megalabs-50 text-megalabs-700 border border-megalabs-200">
                Total: {{ $imports->total() }} lotes registrados
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-megalabs-900 text-xs uppercase font-bold tracking-wider">
                        <th class="py-3.5 px-4 w-16">ID</th>
                        <th class="py-3.5 px-4">Archivo CSV</th>
                        <th class="py-3.5 px-4">Fecha de Carga</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-4 text-right">Total Filas</th>
                        <th class="py-3.5 px-4 text-right text-emerald-700">Válidos</th>
                        <th class="py-3.5 px-4 text-right text-rose-700">Inconsistencias</th>
                        <th class="py-3.5 px-4 text-right text-megalabs-800">Ingresos ($)</th>
                        <th class="py-3.5 px-4 text-center">Tiempo</th>
                        <th class="py-3.5 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($imports as $import)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-500">
                                #{{ $import->id }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 truncate max-w-xs flex items-center">
                                    <i class="fa-solid fa-file-csv text-megalabs-600 mr-2 text-base"></i>
                                    {{ $import->original_name }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500 font-medium">
                                {{ $import->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                @if($import->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i> Completado
                                    </span>
                                @elseif($import->status === 'processing')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                        <i class="fa-solid fa-spinner fa-spin mr-1.5 text-amber-600"></i> Procesando
                                    </span>
                                @elseif($import->status === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        <i class="fa-solid fa-circle-xmark mr-1.5 text-rose-600"></i> Fallido
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <i class="fa-solid fa-clock mr-1.5 text-slate-500"></i> Pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 font-medium">
                                {{ number_format($import->total_rows) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700">
                                {{ number_format($import->successful_rows) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-rose-600">
                                {{ number_format($import->failed_rows) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-megalabs-900">
                                ${{ number_format($import->total_revenue, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center text-xs text-slate-500 font-mono">
                                @if($import->started_at && $import->completed_at)
                                    {{ $import->started_at->diffInSeconds($import->completed_at) }}s
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Ver Detalle (Registros e Inconsistencias) -->
                                    <a href="{{ route('imports.detail', $import->id) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-megalabs-50 hover:bg-megalabs-100 text-megalabs-800 border border-megalabs-200 shadow-sm transition text-xs font-bold" title="Ver Detalle de Registros e Inconsistencias">
                                        <i class="fa-solid fa-list-check mr-1.5 text-megalabs-600"></i> Detalle
                                    </a>

                                    <!-- Ver Reporte (Gráficas BI) -->
                                    <a href="{{ route('imports.report', $import->id) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm transition text-xs font-bold" title="Ver Reporte Analítico y Gráficas">
                                        <i class="fa-solid fa-chart-pie mr-1.5 text-emerald-600"></i> Reporte
                                    </a>

                                    <!-- Eliminar -->
                                    <button type="button" onclick="deleteImport({{ $import->id }})" class="p-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition text-xs shadow-sm" title="Eliminar importación">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-500">
                                <div class="w-16 h-16 mx-auto rounded-2xl bg-megalabs-50 flex items-center justify-center text-megalabs-600 mb-3 border border-megalabs-100">
                                    <i class="fa-solid fa-inbox text-2xl"></i>
                                </div>
                                <p class="text-base font-bold text-slate-700">No hay importaciones registradas todavía.</p>
                                <p class="text-xs text-slate-400 mt-1">Carga tu primer archivo CSV o descarga una muestra de 25K arriba para comenzar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($imports->hasPages())
            <div class="mt-6">
                {{ $imports->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Drag and Drop & Upload Management
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file');
    const dropzoneDefault = document.getElementById('dropzone-default');
    const dropzoneSelected = document.getElementById('dropzone-selected');
    const selectedFileName = document.getElementById('selected-file-name');
    const selectedFileSize = document.getElementById('selected-file-size');
    const btnRemoveFile = document.getElementById('btn-remove-file');
    const uploadForm = document.getElementById('upload-form');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const percentageText = document.getElementById('upload-percentage');
    const btnSubmit = document.getElementById('btn-submit-upload');

    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelection(e.target.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('border-megalabs-500', 'bg-megalabs-50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-megalabs-500', 'bg-megalabs-50');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelection(files[0]);
        }
    });

    btnRemoveFile.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = '';
        dropzoneSelected.classList.add('hidden');
        dropzoneDefault.classList.remove('hidden');
    });

    function handleFileSelection(file) {
        selectedFileName.textContent = file.name;
        selectedFileSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        dropzoneDefault.classList.add('hidden');
        dropzoneSelected.classList.remove('hidden');
    }

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!fileInput.files.length) {
            showToast('Por favor selecciona un archivo CSV primero', 'error');
            return;
        }

        const formData = new FormData(uploadForm);
        btnSubmit.disabled = true;
        progressContainer.classList.remove('hidden');

        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadForm.action, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    percentageText.textContent = percent + '%';
                }
            };

            xhr.onload = () => {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && res.success) {
                        showToast('¡Importación procesada exitosamente!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    } else {
                        showToast(res.message || 'Error al procesar el archivo CSV.', 'error');
                        btnSubmit.disabled = false;
                    }
                } catch (err) {
                    showToast('Error al interpretar respuesta del servidor', 'error');
                    btnSubmit.disabled = false;
                }
            };

            xhr.onerror = () => {
                showToast('Error de red durante la subida.', 'error');
                btnSubmit.disabled = false;
            };

            xhr.send(formData);

        } catch (error) {
            showToast('Ocurrió un error inesperado', 'error');
            btnSubmit.disabled = false;
        }
    });
</script>
@endpush
