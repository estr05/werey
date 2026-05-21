# Lineamientos de Desarrollo (Guidelines) - Ecosistema DevUbi

Este documento establece las reglas, estándares y buenas prácticas obligatorias para el equipo de desarrollo (Análisis, Backend y Mobile) del proyecto **DevUbi**.

---

## 1. Lineamientos del Backend (Laravel)

### 1.1. Estructura y Código Limpio
*   **Controladores:** Deben mantenerse "delgados" (*Skinny Controllers*). La lógica de negocio compleja debe residir en **Services** o **Actions**.
*   **Form Requests:** Toda validación de datos entrantes de la API móvil debe realizarse en clases de tipo `FormRequest`. Queda prohibido validar directamente en el controlador.
*   **API Resources:** Para asegurar un contrato de datos estricto con Flutter, todas las respuestas de la API deben ser transformadas utilizando `JsonResource` de Laravel. **Nunca** se debe retornar un modelo directo (`return response()->json($model)`).

### 1.2. Rutas y API
*   Todas las rutas de la aplicación móvil deben residir en `routes/api.php` y llevar el prefijo `/api/v1/`.
*   Uso estricto de verbos HTTP: `GET` (Consultar), `POST` (Crear), `PUT`/`PATCH` (Actualizar), `DELETE` (Eliminar).

### 1.3. Base de Datos
*   **Migraciones:** Cada cambio en la base de datos debe tener su respectiva migración. No se permiten cambios manuales en el manejador de SQL.
*   **Eloquent:** Aprovechar el uso de *Eager Loading* (`with()`) para evitar el problema de consultas $N+1$.

---

## 2. Lineamientos del Frontend Móvil (Flutter)

### 2.1. Gestión de Estado y Arquitectura
*   **Arquitectura:** Se utilizará una arquitectura limpia separada por capas: *Data* (Repositorios y Providers), *Domain* (Modelos y Casos de Uso) y *Presentation* (UI y Gestor de Estado).
*   **Gestión de Estado:** Se empleará un enfoque reactivo y predecible (ej. BLoC o Riverpod) para separar la UI de la lógica de presentación.

### 2.2. Consumo de API y Datos
*   **Cliente HTTP:** Se utilizará el paquete `dio` para las peticiones HTTP, implementando *Interceptors* para adjuntar automáticamente el Token de Laravel Sanctum y manejar errores globales (errores 401, 403, 500).
*   **Modelado:** Todo JSON recibido de Laravel debe ser parseado a objetos Dart fuertemente tipados utilizando métodos `fromJson` y `toJson`.

### 2.3. Buenas Prácticas de UI
*   **Widgets Constantes:** Usar `const` siempre que sea posible para mejorar el rendimiento del renderizado.
*   **Responsividad:** Diseñar interfaces utilizando componentes flexibles (`LayoutBuilder`, `MediaQuery`) para asegurar la correcta visualización en diferentes tamaños de pantalla.

---

## 3. Estándares de Git y Colaboración

### 3.1. Nombres de Ramas (Branches)
Se trabajará bajo una nomenclatura descriptiva basada en prefijos:
*   `feature/nombre-de-la-caracteristica` (Nuevas funcionalidades)
*   `fix/descripcion-del-error` (Corrección de bugs)
*   `refactor/modulo-refactorizado` (Mejoras de código sin cambiar funcionalidad)

### 3.2. Formato de Commits
Los mensajes de commit deben ser claros y en presente:
*   `feat: add login authentication endpoints`
*   `fix: resolve null pointer in profile screen`
*   `docs: update guideline markdown`

---

## 4. Formato de Respuestas de la API (Contrato)
Para evitar fallos de parseo en Flutter, la API de Laravel siempre responderá bajo la siguiente estructura unificada:

### Respuesta Exitosa (200/201)
```json
{
    "success": true,
    "message": "Operación realizada con éxito.",
    "data": { ... } 
}