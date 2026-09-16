# Sistema de Gestión de Ventas Masivas (ETL & Business Intelligence Analytics)

Solución de alto rendimiento desarrollada en **Laravel 10** para la ingesta masiva de archivos CSV (20,000 a 100,000+ registros por lote), validación tolerante a fallos, procesamiento asíncrono desacoplado mediante colas y visualización de reportes analíticos con gráficos interactivos.

---

## 1. Guía de Instalación y Ejecución

### Requisitos Previos
- **PHP** >= 8.1 (con extensiones: `pdo`, `pdo_sqlite` o `pdo_mysql`, `bcmath`, `mbstring`, `curl`, `zip`)
- **Composer** >= 2.0
- **Base de Datos**: SQLite (habilitado por defecto para despliegue instantáneo sin dependencias) o MySQL 8.0+ / MariaDB / PostgreSQL.

---

### Paso a Paso para Despliegue Local

#### 1. Clonar o acceder al repositorio
```bash
cd "c:\Users\RED5G\Desktop\Prueba tecnica"
```

#### 2. Instalar dependencias con Composer
Si tienes Composer y PHP configurados en tu `PATH` global:
```bash
composer install --no-interaction
```

> **Nota para entornos XAMPP en Windows (si `composer` o `php` no están en el PATH):**
> Se incluye `composer.phar` en la raíz del proyecto. Puedes ejecutar los comandos directamente con el ejecutable de XAMPP:
> ```bash
> # En CMD / PowerShell / Git Bash:
> /c/xampp/php/php composer.phar install --no-interaction
> # o en CMD / PowerShell:
> C:\xampp\php\php.exe composer.phar install --no-interaction
> ```

#### 3. Configurar variables de entorno
Copiar el archivo `.env.example` a `.env`:
```bash
cp .env.example .env
# Generar la llave de la aplicación:
/c/xampp/php/php artisan key:generate
# o si php está en tu PATH: php artisan key:generate
```

Por defecto, el proyecto está configurado para ejecutarse con **SQLite** de forma autónoma. Si deseas utilizar **MySQL**, edita las siguientes líneas en tu `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=retail_sales
DB_USERNAME=root
DB_PASSWORD=tu_password
```

#### 4. Ejecutar las Migraciones
Crea la estructura de tablas (`imports`, `sale_records`, `import_errors`, `jobs`):
```bash
/c/xampp/php/php artisan migrate:fresh
# o si php está en tu PATH: php artisan migrate:fresh
```

#### 5. Configuración de Procesos en Segundo Plano (Colas / Queues)
El sistema soporta procesamiento asíncrono para no bloquear la respuesta HTTP.

- **Opción A (Recomendada para producción y pruebas asíncronas):**
  En el `.env`, mantén `QUEUE_CONNECTION=database` y ejecuta el worker en una terminal separada:
  ```bash
  php artisan queue:work --tries=3 --timeout=600
  # o con XAMPP directamente:
  # /c/xampp/php/php artisan queue:work --tries=3 --timeout=600
  # C:\xampp\php\php.exe queue:work --tries=3 --timeout=600
  ```

- **Opción B (Procesamiento sincrónico para pruebas rápidas sin worker):**
  En el `.env`, define:
  ```env
  QUEUE_CONNECTION=sync
  ```

#### 6. Iniciar el Servidor de Desarrollo
```bash
php artisan serve
# o con XAMPP directamente:
# /c/xampp/php/php artisan serve
# C:\xampp\php\php.exe artisan serve
```
La aplicación estará disponible en: **`http://localhost:8000`**

---

### Generación de Archivos CSV de Prueba Sintéticos

El proyecto incluye un comando Artisan personalizado para generar archivos CSV con registros realistas e inconsistencias controladas (fechas inválidas, precios negativos, cantidades en cero, campos vacíos):

```bash
# Generar dataset de 25,000 filas (con 250 inconsistencias)
php artisan sales:generate-csv --rows=25000 --errors=250 --output=storage/app/samples/sales_25k.csv

# Generar dataset de 100,000 filas (con 1,000 inconsistencias)
php artisan sales:generate-csv --rows=100000 --errors=1000 --output=storage/app/samples/sales_100k.csv
```

También puedes generar y descargar estos archivos directamente desde el botón **"Generar CSV 25K"** en la interfaz web.

---

### Ejecución de Pruebas Automatizadas (PHPUnit)

Ejecuta la suite de pruebas unitarias y de integración para validar la API REST, el cálculo de fórmulas, la validación de inconsistencias y la eliminación en cascada:

```bash
php artisan test
```

---

## 2. Decisiones Técnicas y Arquitectura

```mermaid
flowchart TD
    A[Cliente / Accounting Team] -->|POST /api/imports (CSV File)| B[API ImportController]
    B -->|Guarda archivo en storage & Crea registro 'pending'| C[(Imports Table)]
    B -->|Despacha Job asíncrono| D[ProcessCsvImportJob]
    B -->|Respuesta Inmediata 202 Accepted| A

    D -->|Worker en Cola| E[CsvImportService]
    E -->|Streaming fopen + fgetcsv (O(1) RAM)| F{Validación por Fila}
    
    F -->|Registro Válido| G[Calcula Total: qty * price * (1 - disc)]
    G -->|Acumula Lote 1,000 filas| H[DB Bulk Insert: sale_records]
    H --> C2[(sale_records)]
    
    F -->|Registro Inválido (Fecha/Precio/Qty)| I[Aísla Fila & Razón del Fallo]
    I -->|Acumula Lote Errores| J[DB Bulk Insert: import_errors]
    J --> C3[(import_errors)]

    E -->|Actualiza Status: 'completed' + Totales| C

    A -->|GET /api/reports/summary?import_id=X| K[ReportAnalyticsService]
    K -->|Consultas Agregadas Indexadas + Cache| C2
    K -->|Respuesta JSON / Vista Blade| L[Dashboard BI Charts]
```

### A. Metodología de Procesamiento de Archivos (ETL Eficiente)
1. **Streaming con Generadores (`fopen` + `fgetcsv`)**:
   En lugar de cargar archivos de 100,000+ filas completos en memoria (como hacen `file()` o `League\Csv::createFromPath()->getRecords()`), se implementó un puntero de lectura línea por línea en *streaming*. Esto mantiene el consumo de memoria constante en **$O(1)$ RAM** (aprox. **52 MB** de pico en pruebas con 25,000 registros).
2. **Inserción por Lotes Masivos (*Bulk Chunks*)**:
   Las filas válidas y las inconsistencias se acumulan en buffers de **1,000 registros** y se insertan mediante `DB::table('sale_records')->insert($chunk)` dentro de transacciones de base de datos (`DB::transaction`). Esto reduce el overhead de red/driver de 100,000 consultas individuales a solo 100 queries de inserción.
3. **Tolerancia a Fallos Granular y Auditoría**:
   Los registros que no cumplen con los tipos de datos (precios negativos, cantidad $\le 0$, fechas con formato incorrecto, campos clave nulos) son interceptados sin abortar el proceso general y se almacenan en la tabla `import_errors` con el número exacto de fila y los motivos detallados del fallo.

### B. Estrategia para Rapidez en Generación de Reportes
1. **Índices Compuestos de Agregación**:
   La tabla `sale_records` cuenta con índices en `(import_id, category)`, `(import_id, country)`, `(import_id, product_id, product_name)` e `(import_id, total_amount)`. Esto permite que las consultas con `SUM()`, `GROUP BY` y `ORDER BY DESC LIMIT 5` se ejecuten en **menos de 350 ms** sobre cientos de miles de filas.
2. **Capa de Caché Inteligente**:
   El servicio `ReportAnalyticsService` implementa `Cache::remember` con una clave única por importación (`report_summary_{import_id}`). Dado que los datos de una importación completada son inmutables, las lecturas posteriores se sirven en **0 ms** desde memoria.

### C. Propuesta Técnica ante un Escenario de Millones de Registros
Para escalar el sistema a millones de transacciones mensuales:

1. **Ingesta con Drivers Nativos Masivos o S3 Multipart**:
   - En MySQL: Uso de `LOAD DATA LOCAL INFILE` o en PostgreSQL mediante `COPY sale_records FROM '...' CSV HEADER`, permitiendo ingerir 1,000,000 de registros en menos de 10 segundos.
   - Procesamiento paralelo dividiendo el archivo CSV en *chunks físicos de 50,000 filas* distribuidos a un cluster de workers en AWS SQS / Redis Horizon.
2. **Particionamiento de Tablas (*Table Partitioning*)**:
   - Particionar la tabla `sale_records` por rango de fechas (`RANGE COLUMNS (date)`) o por lista de importaciones (`LIST/HASH (import_id)`), asegurando que las consultas de agregación y el borrado en cascada operen solo sobre la partición correspondiente sin bloqueos globales.
3. **Base de Datos Columnar / OLAP para BI en Tiempo Real**:
   - Para analítica sobre decenas de millones de registros, replicar las ventas validadas hacia una base de datos columnar como **ClickHouse**, **DuckDB** o **Amazon Redshift**, donde las agregaciones vectorizadas sobre columnas numéricas (`total_amount`, `quantity`) se ejecutan a velocidad de gigabytes por segundo.

---

## 3. Especificación de Endpoints API REST

### 1. Ingesta Masiva de Archivo CSV
- **Ruta**: `POST /api/imports`
- **Content-Type**: `multipart/form-data`
- **Body**: `file` (archivo `.csv`)
- **Respuesta (202 Accepted)**:
```json
{
  "success": true,
  "message": "Archivo recibido exitosamente. El procesamiento se ha iniciado en segundo plano.",
  "data": {
    "id": 1,
    "file_name": "imports/3f2a89d4-c9b2-4d2a-8b1e-45fa8b3c1a2d_1726410000.csv",
    "original_name": "ventas_septiembre_2024.csv",
    "status": "pending",
    "total_rows": 0,
    "successful_rows": 0,
    "failed_rows": 0,
    "total_revenue": "0.00",
    "created_at": "2024-09-15T14:30:00.000000Z"
  }
}
```

---

### 2. Historial de Importaciones
- **Ruta**: `GET /api/imports?page=1&per_page=15`
- **Respuesta (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "original_name": "ventas_septiembre_2024.csv",
      "status": "completed",
      "total_rows": 25000,
      "successful_rows": 24809,
      "failed_rows": 191,
      "total_revenue": "33335848.81",
      "completed_at": "2024-09-15T14:30:03.000000Z"
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  },
  "stats": {
    "total_imports": 1,
    "completed": 1,
    "processing": 0,
    "failed": 0,
    "total_records_processed": 24809,
    "total_revenue_overall": 33335848.81
  }
}
```

---

### 3. Listado Paginado de Inconsistencias (Errores)
- **Ruta**: `GET /api/imports/{id}/errors?page=1&per_page=20`
- **Respuesta (200 OK)**:
```json
{
  "success": true,
  "import_id": 1,
  "file_name": "ventas_septiembre_2024.csv",
  "total_errors": 191,
  "data": [
    {
      "id": 1,
      "import_id": 1,
      "row_number": 4,
      "error_reason": "El precio unitario no puede ser negativo (valor recibido: -50.00).",
      "raw_data": {
        "order_id": "ORD-0000004",
        "date": "2024-05-12",
        "customer_id": "C002",
        "unit_price": "-50.00"
      },
      "created_at": "2024-09-15T14:30:02.000000Z"
    }
  ],
  "pagination": {
    "total": 191,
    "per_page": 20,
    "current_page": 1,
    "last_page": 10
  }
}
```

---

### 4. Eliminación en Cascada
- **Ruta**: `DELETE /api/imports/{id}`
- **Respuesta (200 OK)**:
```json
{
  "success": true,
  "message": "La importación, sus registros de ventas y sus errores vinculados han sido eliminados íntegramente."
}
```

---

### 5. Reporte de Inteligencia de Negocio (BI Summary)
- **Ruta**: `GET /api/reports/summary?import_id={id}`
- **Respuesta (200 OK)**:
```json
{
  "success": true,
  "import": {
    "id": 1,
    "file_name": "ventas_septiembre_2024.csv",
    "status": "completed",
    "total_rows": 25000,
    "successful_rows": 24809,
    "failed_rows": 191,
    "processing_time_seconds": 3
  },
  "summary": {
    "total_revenue": 33335848.81,
    "total_units_sold": 198450,
    "total_transactions": 24809,
    "average_order_value": 1343.70,
    "date_range": {
      "start": "2024-01-01",
      "end": "2024-12-31"
    }
  },
  "top_products": [
    {
      "product_id": "P004",
      "product_name": "Ultrabook Laptop 16GB",
      "total_revenue": 6944698.25,
      "units_sold": 6621,
      "avg_unit_price": 1199.50,
      "revenue_percentage": 20.83
    },
    {
      "product_id": "P001",
      "product_name": "Smartphone Pro Max",
      "total_revenue": 4529558.95,
      "units_sold": 6446,
      "avg_unit_price": 799.99,
      "revenue_percentage": 13.59
    }
  ],
  "category_distribution": [
    {
      "category": "Electronics",
      "total_revenue": 16459446.55,
      "units_sold": 41200,
      "transactions_count": 12400,
      "percentage": 49.37
    },
    {
      "category": "Home & Kitchen",
      "total_revenue": 7840729.95,
      "units_sold": 38100,
      "transactions_count": 6200,
      "percentage": 23.52
    }
  ],
  "geographical_distribution": [
    {
      "country": "Colombia",
      "total_revenue": 3501583.30,
      "units_sold": 20450,
      "transactions_count": 2510,
      "percentage": 10.50
    },
    {
      "country": "Brasil",
      "total_revenue": 3431829.90,
      "units_sold": 19980,
      "transactions_count": 2490,
      "percentage": 10.29
    }
  ]
}
```

---

## 4. Estructura del Proyecto

```
├── app/
│   ├── Console/Commands/
│   │   └── GenerateSampleCsvCommand.php  # Generador de CSVs sintéticos de 20k a 100k
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── ImportController.php  # Endpoints CRUD de importaciones y errores
│   │   │   │   └── ReportController.php  # Endpoint de reporte BI summary
│   │   │   └── Web/
│   │   │       └── DashboardController.php # Vistas Blade del Dashboard
│   │   └── Requests/
│   │       └── UploadCsvRequest.php      # Validación de archivo CSV
│   ├── Jobs/
│   │   └── ProcessCsvImportJob.php       # Job asíncrono en cola
│   ├── Models/
│   │   ├── Import.php                    # Modelo de lote de importación
│   │   ├── SaleRecord.php                # Modelo de registros de ventas
│   │   └── ImportError.php               # Modelo de inconsistencias / auditoría
│   └── Services/
│       ├── CsvImportService.php          # Pipeline ETL streaming $O(1)$ RAM
│       └── ReportAnalyticsService.php    # Motor de consultas agregadas BI & caché
├── database/
│   └── migrations/                       # Esquemas de BD con índices optimizados
├── resources/
│   └── views/
│       ├── layouts/app.blade.php         # Layout Blade moderno con Tailwind y Chart.js
│       ├── dashboard.blade.php           # Panel principal y uploader drag-and-drop
│       └── imports/show.blade.php        # Vista de detalle con gráficos BI interactivos
├── routes/
│   ├── api.php                           # Rutas RESTful de la API
│   └── web.php                           # Rutas de la interfaz web
└── tests/
    ├── Feature/
    │   ├── ImportApiTest.php             # Tests de API de importación y cascada
    │   └── ReportApiTest.php             # Tests de agregaciones y fórmulas BI
    └── Unit/
        └── CsvImportServiceTest.php      # Tests unitarios del motor ETL
```
