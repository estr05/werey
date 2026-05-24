# 📱 PROMPT PARA ARREGLAR APP MÓVIL (warey_movil)

**Instrucciones:** Copia y pega este bloque en Codex, AntiGravity CLI, o cualquier asistente de IA para que aplique los cambios directamente sobre el repositorio `github.com/estr05/warey_movil.git`.

---

## Contexto del Proyecto

Eres un asistente de IA trabajando en el proyecto **warey_movil**, una app Flutter de tracking y telemetría. La app envía datos a un backend Laravel. Se detectaron **inconsistencias de integración** entre lo que envía la app móvil y lo que el backend espera recibir.

### Arquitectura actual de la app móvil:

La app tiene **3 fuentes de datos** que envían información al backend:

| Fuente | Archivo | Endpoint que llama | Datos que envía |
|--------|---------|-------------------|-----------------|
| **TelemetryEngine** | `telemetry_engine.dart` → `telemetry_repository_impl.dart` | `POST /telemetry` | lat, lng, battery_level, is_charging, connection_type, **created_at** |
| **DeviceStatusRepository** | `device_status_repository.dart` (parte de `tracking_engine.dart`) | `POST /telemetry` ❌ | battery_level, is_charging, connection_type, signal_strength, has_internet, tracking_state, activity_status, **captured_at** |
| **LocationRepository** | `location_repository.dart` (parte de `tracking_engine.dart`) | `POST /location` ✅ | lat, lng, accuracy, speed, altitude, movement_type, tracking_state, is_safe_zone, zone_name, captured_at |

---

## 🔴 CAMBIO #1 (CRÍTICO): DeviceStatusRepository envía al endpoint incorrecto

**Archivo:** `lib/features/tracking/data/repositories/device_status_repository.dart`

**Problema:** El repositorio envía los frames de estado del dispositivo al endpoint `'telemetry'`, pero debería enviarlos a `'device-status'`. El endpoint `telemetry` requiere latitud/longitud (aunque ahora son opcionales), pero conceptualmente estos datos son de estado del dispositivo, no de telemetría GPS.

**Cambio exacto (línea ~72):**

```dart
// ❌ ANTES:
final response = await _dio.post<Map<String, dynamic>>(
  'telemetry',
  data: frame.toApiJson(),
);

// ✅ DESPUÉS:
final response = await _dio.post<Map<String, dynamic>>(
  'device-status',
  data: frame.toApiJson(),
);
```

**Impacto:** Sin este cambio, el backend recibe los frames de estado en el endpoint de telemetría. Aunque el backend ahora acepta frames sin GPS, es mejor usar el endpoint correcto para separar responsabilidades.

---

## 🔴 CAMBIO #2: Agregar `screenActive` al modelo DeviceStatusFrame y TrackingEngine

### 2a. Modelo `DeviceStatusFrame`

**Archivo:** `lib/features/tracking/domain/models/device_status_frame.dart`

Agregar el campo `screenActive` (bool) al modelo:

```dart
// ANTES:
class DeviceStatusFrame {
  final int batteryLevel;
  final bool isCharging;
  final ConnectionType connectionType;
  final int? signalStrength;
  final bool hasInternetAccess;
  final String trackingState;
  final String activityStatus;
  final DateTime capturedAt;

  const DeviceStatusFrame({
    required this.batteryLevel,
    required this.isCharging,
    required this.connectionType,
    this.signalStrength,
    required this.hasInternetAccess,
    required this.trackingState,
    required this.activityStatus,
    required this.capturedAt,
  });

// DESPUÉS:
class DeviceStatusFrame {
  final int batteryLevel;
  final bool isCharging;
  final ConnectionType connectionType;
  final int? signalStrength;
  final bool hasInternetAccess;
  final String trackingState;
  final String activityStatus;
  final bool screenActive;       // ✅ NUEVO
  final DateTime capturedAt;

  const DeviceStatusFrame({
    required this.batteryLevel,
    required this.isCharging,
    required this.connectionType,
    this.signalStrength,
    required this.hasInternetAccess,
    required this.trackingState,
    required this.activityStatus,
    required this.screenActive,   // ✅ NUEVO
    required this.capturedAt,
  });
```

### 2b. Actualizar `toApiJson()` en el mismo archivo

```dart
// ANTES:
Map<String, dynamic> toApiJson() => {
      'battery_level': batteryLevel,
      'is_charging': isCharging,
      'connection_type': connectionType.name,
      'signal_strength': signalStrength,
      'has_internet': hasInternetAccess,
      'tracking_state': trackingState,
      'activity_status': activityStatus,
      'captured_at': capturedAt.toIso8601String(),
    };

// DESPUÉS:
Map<String, dynamic> toApiJson() => {
      'battery_level': batteryLevel,
      'is_charging': isCharging,
      'connection_type': connectionType.name,
      'signal_strength': signalStrength,
      'has_internet': hasInternetAccess,
      'tracking_state': trackingState,
      'activity_status': activityStatus,
      'screen_active': screenActive,  // ✅ NUEVO
      'captured_at': capturedAt.toIso8601String(),
    };
```

### 2c. Actualizar `toLocalMap()` (si existe, para SQLite local)

```dart
// Busca el método toLocalMap() y fromLocalMap() en el mismo archivo
// y agrega 'screen_active' al map.
```

### 2d. Actualizar `TrackingEngine` para capturar `screenActive`

**Archivo:** `lib/features/tracking/domain/engines/tracking_engine.dart`

Busca donde se construye `DeviceStatusFrame` y agrega el estado de la pantalla:

```dart
// ANTES (aproximadamente, busca la creación de DeviceStatusFrame):
DeviceStatusFrame(
  batteryLevel: batteryLevel,
  isCharging: isCharging,
  connectionType: connectionType,
  signalStrength: signalStrength,
  hasInternetAccess: hasInternetAccess,
  trackingState: trackingState,
  activityStatus: activityStatus,
  capturedAt: DateTime.now(),
)

// DESPUÉS:
DeviceStatusFrame(
  batteryLevel: batteryLevel,
  isCharging: isCharging,
  connectionType: connectionType,
  signalStrength: signalStrength,
  hasInternetAccess: hasInternetAccess,
  trackingState: trackingState,
  activityStatus: activityStatus,
  screenActive: WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed,  // ✅ NUEVO
  capturedAt: DateTime.now(),
)
```

**Nota:** Es posible que necesites importar `import 'package:flutter/widgets.dart';` y usar un `ValueNotifier` o pasar el estado de la pantalla desde el `TrackingEngine` si no tiene acceso directo a `WidgetsBinding`. Otra opción más limpia es pasar `screenActive` como parámetro desde el `TrackingService` o usar un `PlatformDispatcher`.

---

## 🟡 CAMBIO #3: TelemetryRepositoryImpl envía `created_at` en vez de `captured_at`

**Archivo:** `lib/features/telemetry/data/repositories/telemetry_repository_impl.dart`

**Problema:** El payload envía `created_at` pero el backend espera `captured_at`. Aunque el backend ahora tiene un fallback para `created_at`, es mejor enviar el nombre correcto.

**Cambio exacto:**

```dart
// ANTES:
'created_at': DateTime.now().toIso8601String(),

// DESPUÉS:
'captured_at': DateTime.now().toIso8601String(),
```

---

## 🟢 CAMBIO #4 (OPCIONAL - Mejora): Agregar más campos al TelemetryEngine

**Archivo:** `lib/features/telemetry/domain/engines/telemetry_engine.dart`
**Archivo:** `lib/features/telemetry/data/repositories/telemetry_repository_impl.dart`

**Problema:** El `TelemetryEngine` solo envía 6 campos (lat, lng, battery, charging, connection, created_at). Podría enviar también `movement_type` y `screen_active` para que el backend tenga datos más completos.

**Cambio en `telemetry_repository_impl.dart`** (agregar al payload):

```dart
// En el método toApiJson() o donde se construye el Map:
final payload = {
  'latitude': latitude,
  'longitude': longitude,
  'battery_level': batteryLevel,
  'is_charging': isCharging,
  'connection_type': connectionType,
  'captured_at': DateTime.now().toIso8601String(),
  // ✅ OPCIONAL: agregar estos campos si están disponibles
  if (movementType != null) 'movement_type': movementType,
  if (screenActive != null) 'screen_active': screenActive,
};
```

---

## Resumen de Archivos a Modificar

| # | Archivo | Cambio |
|---|---------|--------|
| 1 | `lib/features/tracking/data/repositories/device_status_repository.dart` | Cambiar endpoint de `'telemetry'` a `'device-status'` |
| 2a | `lib/features/tracking/domain/models/device_status_frame.dart` | Agregar `screenActive` field, constructor, `toApiJson()`, `toLocalMap/fromLocalMap` |
| 2b | `lib/features/tracking/domain/engines/tracking_engine.dart` | Capturar `screenActive` al construir `DeviceStatusFrame` |
| 3 | `lib/features/telemetry/data/repositories/telemetry_repository_impl.dart` | Cambiar `created_at` → `captured_at` |
| 4 | Opcional: `telemetry_engine.dart` + `telemetry_repository_impl.dart` | Agregar `movement_type` y `screen_active` al payload |

---

## Estado del Backend (ya corregido)

Del lado del backend (Laravel) **YA** se hicieron estos arreglos:

- ✅ `TelemetryController` acepta frames sin lat/lng (nullable)
- ✅ Almacena todos los campos: `signal_strength`, `has_internet`, `tracking_state`, `activity_status`, `screen_active`, `captured_at`
- ✅ Ruta `telemetry` corregida (sin trailing slash)
- ✅ Acepta `created_at` como fallback de `captured_at`
- ✅ `DeviceStatusController` existe y funciona en `POST /api/v1/device-status`
- ✅ Dashboard muestra `signal_strength`, `has_internet`, `tracking_state`, `activity_status`
- ✅ Device-detail muestra todos los campos de estado
- ✅ `DeviceApiController` devuelve los nuevos campos en la API

**Una vez que apliques estos 3 cambios en la app móvil, TODO el flujo de datos funcionará correctamente.** 🚀
