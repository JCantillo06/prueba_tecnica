<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Megalabs Analytics') - Somos Bienestar</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        megalabs: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#00875A',
                            800: '#00704A',
                            900: '#00583A',
                            950: '#003825',
                            corporate: '#00875A',
                            brand: '#00A86B',
                            dark: '#00593B',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .glass-header {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }
        .white-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 135, 90, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
        }
        .gradient-text-megalabs {
            background: linear-gradient(135deg, #00875A 0%, #059669 50%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #00875A;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen">

    <!-- Navigation Header (Light Megalabs Style) -->
    <header class="glass-header sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand / Logo Megalabs -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3.5 group">
                        <!-- Megalabs clover symbol -->
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-megalabs-700 to-megalabs-500 p-0.5 shadow-md shadow-megalabs-700/15 group-hover:scale-105 transition-transform duration-200">
                            <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center">
                                <svg class="w-7 h-7" viewBox="0 0 100 100" fill="currentColor">
                                    <circle cx="35" cy="35" r="20" opacity="0.95" fill="#00875A" />
                                    <circle cx="65" cy="35" r="20" opacity="0.85" fill="#059669" />
                                    <circle cx="35" cy="65" r="20" opacity="0.85" fill="#10b981" />
                                    <circle cx="65" cy="65" r="20" opacity="0.95" fill="#00704A" />
                                    <path d="M50 30 Q50 50 30 50 Q50 50 50 70 Q50 50 70 50 Q50 50 50 30 Z" fill="#ffffff"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-extrabold text-megalabs-800 tracking-tight leading-tight flex items-center">
                                Megalabs
                                <span class="ml-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-megalabs-50 text-megalabs-700 border border-megalabs-200">Analytics</span>
                            </span>
                            <span class="text-xs text-slate-500 font-medium tracking-wide">Somos bienestar</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links & Actions -->
                <!-- <div class="flex items-center space-x-3 sm:space-x-4">
                    <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-megalabs-50 text-megalabs-800 border border-megalabs-200 shadow-sm' : 'text-slate-600 hover:text-megalabs-700 hover:bg-slate-100' }} transition flex items-center">
                        <i class="fa-solid fa-layer-group mr-2 text-megalabs-600"></i> Gestor de Importaciones
                    </a>

                    <a href="{{ route('samples.generate', ['rows' => 25000, 'errors' => 250]) }}" class="hidden sm:inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-megalabs-50 text-slate-700 hover:text-megalabs-800 border border-slate-200 hover:border-megalabs-300 shadow-sm transition" title="Generar CSV de prueba">
                        <i class="fa-solid fa-file-csv text-megalabs-600 mr-2 text-sm"></i> Generar CSV 25K
                    </a>

                    <a href="/api/imports" target="_blank" class="text-xs px-3 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-600 hover:text-megalabs-700 border border-slate-200 shadow-sm transition" title="API Docs">
                        <i class="fa-solid fa-code mr-1.5 text-megalabs-600"></i> API Docs
                    </a>
                </div> -->
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg mr-3"></i>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg mr-3"></i>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 py-6 bg-white text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-megalabs-800">Megalabs</span>
                <span>•</span>
                <span>Plataforma de Analítica e Ingesta Masiva de Ventas</span>
            </div>
            <div class="flex items-center space-x-6 text-slate-600">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-megalabs-800">Desarrollador</span>
                    <span>•</span>
                    <span>Juan Carlos Cantillo S.</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Confirmation Modal for Deletion -->
    <div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden transition-all duration-200 opacity-0">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 transform scale-95 transition-all duration-200" id="delete-modal-content">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 mb-4">
                <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-bold text-slate-900">¿Eliminar Lote de Importación?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Esta acción eliminará de forma permanente el archivo CSV, todos los registros de ventas asociados y el historial de inconsistencias. Esta operación no se puede deshacer.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-delete" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-600/20 transition flex items-center justify-center">
                    <i class="fa-solid fa-trash-can mr-1.5"></i> Sí, Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-50 flex flex-col space-y-3 pointer-events-none"></div>

    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-center p-4 rounded-2xl shadow-xl transition-all transform translate-y-4 opacity-0 duration-300 border ${
                type === 'success' ? 'bg-white text-emerald-900 border-emerald-200 shadow-emerald-600/10' :
                type === 'error' ? 'bg-white text-rose-900 border-rose-200 shadow-rose-600/10' :
                'bg-white text-megalabs-900 border-megalabs-200 shadow-megalabs-600/10'
            }`;

            const icon = type === 'success' ? 'fa-circle-check text-emerald-600' :
                         type === 'error' ? 'fa-triangle-exclamation text-rose-600' : 'fa-info-circle text-megalabs-600';

            toast.innerHTML = `
                <i class="fa-solid ${icon} text-lg mr-3"></i>
                <div class="text-sm font-semibold mr-4">${message}</div>
                <button class="text-slate-400 hover:text-slate-700 text-sm ml-auto" onclick="this.parentElement.remove()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-y-4', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-4', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }

        // Global Modal Delete Handler
        let deleteImportTargetId = null;
        let deleteRedirectUrl = null;

        function deleteImport(id, redirectUrl = null) {
            deleteImportTargetId = id;
            deleteRedirectUrl = redirectUrl;
            const modal = document.getElementById('delete-modal');
            const content = document.getElementById('delete-modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            const content = document.getElementById('delete-modal-content');
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                deleteImportTargetId = null;
                deleteRedirectUrl = null;
            }, 200);
        }

        document.getElementById('btn-confirm-delete').addEventListener('click', async () => {
            if (!deleteImportTargetId) return;

            const id = deleteImportTargetId;
            const redirect = deleteRedirectUrl;
            const btn = document.getElementById('btn-confirm-delete');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Eliminando...';

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
                closeDeleteModal();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        if (redirect) {
                            window.location.href = redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 800);
                } else {
                    showToast(data.message || 'Error al eliminar la importación', 'error');
                }
            } catch (e) {
                closeDeleteModal();
                showToast('Error de red al intentar eliminar', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can mr-1.5"></i> Sí, Eliminar';
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
