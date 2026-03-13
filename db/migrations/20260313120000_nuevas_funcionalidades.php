<?php

use Phinx\Migration\AbstractMigration;

class NuevasFuncionalidades extends AbstractMigration
{
    public function change()
    {
        // ============================================
        // TABLA: documentos_adjuntos
        // Almacena documentos PDF, imágenes, etc. por equipo
        // ============================================
        if (!$this->hasTable('documentos_adjuntos')) {
            $table = $this->table('documentos_adjuntos', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('equipo_id', 'integer', ['null' => true])
                  ->addColumn('persona_id', 'integer', ['null' => true])
                  ->addColumn('tipo_documento', 'string', ['limit' => 50]) // factura, garantia, Manual, otro
                  ->addColumn('nombre_original', 'string', ['limit' => 255])
                  ->addColumn('nombre_archivo', 'string', ['limit' => 255])
                  ->addColumn('ruta', 'string', ['limit' => 500])
                  ->addColumn('tamano', 'integer') // bytes
                  ->addColumn('mime_type', 'string', ['limit' => 100])
                  ->addColumn('descripcion', 'text', ['null' => true])
                  ->addColumn('usuario_id', 'integer')
                  ->addColumn('created_at', 'datetime')
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->addForeignKey('persona_id', 'personas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: configuraciones_email
        // Almacena configuración SMTP para notificaciones
        // ============================================
        if (!$this->hasTable('configuraciones_email')) {
            $table = $this->table('configuraciones_email', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('smtp_host', 'string', ['limit' => 200])
                  ->addColumn('smtp_port', 'integer')
                  ->addColumn('smtp_username', 'string', ['limit' => 200])
                  ->addColumn('smtp_password', 'string', ['limit' => 255]) // encriptada
                  ->addColumn('smtp_encryption', 'string', ['limit' => 10]) // tls, ssl
                  ->addColumn('email_from', 'string', ['limit' => 200])
                  ->addColumn('email_from_nombre', 'string', ['limit' => 100])
                  ->addColumn('notificar_asignacion', 'boolean', ['default' => true])
                  ->addColumn('notificar_devolucion', 'boolean', ['default' => true])
                  ->addColumn('notificar_vencimiento', 'boolean', ['default' => true])
                  ->addColumn('dias_antes_vencimiento', 'integer', ['default' => 3])
                  ->addColumn('activo', 'boolean', ['default' => false])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->create();
        }

        // ============================================
        // TABLA: notificaciones
        // Registro de notificaciones enviadas
        // ============================================
        if (!$this->hasTable('notificaciones')) {
            $table = $this->table('notificaciones', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('tipo', 'string', ['limit' => 50]) // asignacion, devolucion, vencimiento, mantenimiento
                  ->addColumn('titulo', 'string', ['limit' => 200])
                  ->addColumn('mensaje', 'text')
                  ->addColumn('email_destino', 'string', ['limit' => 200, 'null' => true])
                  ->addColumn('equipo_id', 'integer', ['null' => true])
                  ->addColumn('persona_id', 'integer', ['null' => true])
                  ->addColumn('enviado', 'boolean', ['default' => false])
                  ->addColumn('fecha_envio', 'datetime', ['null' => true])
                  ->addColumn('error', 'text', ['null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->create();
        }

        // ============================================
        // TABLA: equipos_fotos
        // Múltiples fotos por equipo
        // ============================================
        if (!$this->hasTable('equipos_fotos')) {
            $table = $this->table('equipos_fotos', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('equipo_id', 'integer')
                  ->addColumn('nombre_archivo', 'string', ['limit' => 255])
                  ->addColumn('ruta', 'string', ['limit' => 500])
                  ->addColumn('descripcion', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('es_principal', 'boolean', ['default' => false])
                  ->addColumn('usuario_id', 'integer')
                  ->addColumn('created_at', 'datetime')
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: preferencias_usuario
        // Preferencias como tema oscuro
        // ============================================
        if (!$this->hasTable('preferencias_usuario')) {
            $table = $this->table('preferencias_usuario', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('usuario_id', 'integer')
                  ->addColumn('clave', 'string', ['limit' => 50]) // tema, idioma, etc
                  ->addColumn('valor', 'text')
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->create();
        }
    }
}
