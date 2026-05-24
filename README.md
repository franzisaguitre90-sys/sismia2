# sismia2 Saas
## SISMIA2 – Sistema de Monitoreo e Información Ambiental v2.0 

Desarrollado para la Unidades de A)Saneamiento Básico Municipales en intercomunicacion con B)Gobernacion Departamental SISMIA2 es una plataforma web que digitaliza y automatiza la gestión de trámites de **Habilitación Ambiental** bajo el marco de la **Ley 1333 de Bolivia** y las categorias que represntan gestionar. Permite hacer seguimiento completo del ciclo de vida de una Hoja de Ruta (HDR): desde que el consultor la ingresa, pasando por la revisión técnica, hasta la emisión del certificado de habilitación por secretaría.

El sistema implementa **4 roles diferenciados** con dashboards de color único por rol, control de sesión única, sistema de alertas por vencimiento de plazos, KPIs por técnico, gestión de turnos, recordatorios, carga de documentos con verificación de integridad y registro de auditoría completo.


## Mejoras pendientes

### Prioridad alta
- **Exportación PDF/Excel** — certificados de habilitación, reportes de KPI y listados de trámites imprimibles directamente desde el sistema
- **Envío de correo electrónico** — notificaciones automáticas vía SMTP (PHPMailer) al consultor cuando su trámite cambia de estado
- **Recuperación de contraseña por email** — flujo completo con token temporal en lugar de solo mostrar la clave única

### Prioridad media
- **Módulo de búsqueda pública** — portal donde cualquier ciudadano pueda consultar el estado de una habilitación ingresando el NIT o número de RAI, sin login
- **Firma digital / QR en certificados** — código QR en el PDF del certificado que enlace a la verificación pública
- **Historial de versiones del formulario RAI** — guardar cada edición del formulario como versión, no sobreescribir
- **Panel de estadísticas avanzado** — gráficos por municipio, categoría RAI, mes y técnico para el director

### Prioridad baja
- **App móvil / PWA** — convertir el sistema en Progressive Web App para uso desde celular sin conexión
- **Integración con sistemas externos** — conexión con el SIARH o plataformas nacionales de medio ambiente
- **Sistema de chat interno** — mensajería entre técnico y consultor dentro del trámite, reemplazando el campo de observaciones
- **Autenticación de dos factores (2FA)** — para roles director, secretaria y admin
