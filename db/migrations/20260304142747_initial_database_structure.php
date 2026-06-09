
<?php

use Phinx\Migration\AbstractMigration;

class InitialDatabaseStructure extends AbstractMigration
{
    public function change()
    {
        // ============================================
        // TABLA: usuarios
        // ============================================
        if (!$this->hasTable('usuarios')) {
            $table = $this->table('usuarios', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('nombre', 'string', ['limit' => 100])
                  ->addColumn('email', 'string', ['limit' => 150])
                  ->addColumn('password', 'string', ['limit' => 255])
                  ->addColumn('rol', 'string', ['limit' => 50])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['email'], ['unique' => true])
                  ->create();
        }

        // ============================================
        // TABLA: personas
        // ============================================
        if (!$this->hasTable('personas')) {
            $table = $this->table('personas', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('nombre', 'string', ['limit' => 100])
                  ->addColumn('apellido', 'string', ['limit' => 100])
                  ->addColumn('cedula', 'string', ['limit' => 20])
                  ->addColumn('cargo', 'string', ['limit' => 100])
                  ->addColumn('departamento', 'string', ['limit' => 100])
                  ->addColumn('email', 'string', ['limit' => 150, 'null' => true])
                  ->addColumn('telefono', 'string', ['limit' => 20, 'null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['cedula'], ['unique' => true])
                  ->create();
        }

        // ============================================
        // TABLA: ubicaciones
        // ============================================
        if (!$this->hasTable('ubicaciones')) {
            $table = $this->table('ubicaciones', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('nombre', 'string', ['limit' => 100])
                  ->addColumn('descripcion', 'text', ['null' => true])
                  ->addColumn('direccion', 'text', ['null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->create();
        }

        // ============================================
        // TABLA: equipos
        // ============================================
        if (!$this->hasTable('equipos')) {
            $table = $this->table('equipos', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('marca', 'string', ['limit' => 50])
                  ->addColumn('modelo', 'string', ['limit' => 100])
                  ->addColumn('serie', 'string', ['limit' => 100])
                  ->addColumn('activo_fijo', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('estado', 'string', ['limit' => 50])
                  ->addColumn('ubicacion_id', 'integer', ['null' => true])
                  ->addColumn('persona_id', 'integer', ['null' => true])
                  ->addColumn('fecha_ingreso', 'date')
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['serie'], ['unique' => true])
                  ->addIndex(['activo_fijo'])
                  ->addForeignKey('ubicacion_id', 'ubicaciones', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('persona_id', 'personas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: componentes
        // ============================================
        if (!$this->hasTable('componentes')) {
            $table = $this->table('componentes', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('marca', 'string', ['limit' => 50])
                  ->addColumn('modelo', 'string', ['limit' => 100])
                  ->addColumn('serie', 'string', ['limit' => 100])
                  ->addColumn('capacidad', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('estado', 'string', ['limit' => 50])
                  ->addColumn('ubicacion_id', 'integer', ['null' => true])
                  ->addColumn('equipo_id', 'integer', ['null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['serie'], ['unique' => true])
                  ->addForeignKey('ubicacion_id', 'ubicaciones', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: asignaciones
        // ============================================
        if (!$this->hasTable('asignaciones')) {
            $table = $this->table('asignaciones', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('persona_id', 'integer')
                  ->addColumn('equipo_id', 'integer', ['null' => true])
                  ->addColumn('fecha_asignacion', 'date')
                  ->addColumn('fecha_devolucion', 'date', ['null' => true])
                  ->addColumn('estado', 'string', ['limit' => 50])
                  ->addColumn('observaciones', 'text', ['null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addForeignKey('persona_id', 'personas', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: asignaciones_componentes
        // ============================================
        if (!$this->hasTable('asignaciones_componentes')) {
            $table = $this->table('asignaciones_componentes', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('asignacion_id', 'integer')
                  ->addColumn('componente_id', 'integer')
                  ->addColumn('created_at', 'datetime')
                  ->addForeignKey('asignacion_id', 'asignaciones', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->addForeignKey('componente_id', 'componentes', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: movimientos
        // ============================================
        if (!$this->hasTable('movimientos')) {
            $table = $this->table('movimientos', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('equipo_id', 'integer', ['null' => true])
                  ->addColumn('persona_origen_id', 'integer', ['null' => true])
                  ->addColumn('persona_destino_id', 'integer', ['null' => true])
                  ->addColumn('ubicacion_origen_id', 'integer', ['null' => true])
                  ->addColumn('ubicacion_destino_id', 'integer', ['null' => true])
                  ->addColumn('fecha', 'datetime')
                  ->addColumn('observaciones', 'text', ['null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('persona_origen_id', 'personas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('persona_destino_id', 'personas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('ubicacion_origen_id', 'ubicaciones', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->addForeignKey('ubicacion_destino_id', 'ubicaciones', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: movimientos_componentes
        // ============================================
        if (!$this->hasTable('movimientos_componentes')) {
            $table = $this->table('movimientos_componentes', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('movimiento_id', 'integer')
                  ->addColumn('componente_id', 'integer')
                  ->addColumn('created_at', 'datetime')
                  ->addForeignKey('movimiento_id', 'movimientos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->addForeignKey('componente_id', 'componentes', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: incidencias
        // ============================================
        if (!$this->hasTable('incidencias')) {
            $table = $this->table('incidencias', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('equipo_id', 'integer')
                  ->addColumn('persona_id', 'integer')
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('descripcion', 'text')
                  ->addColumn('fecha_reporte', 'datetime')
                  ->addColumn('fecha_solucion', 'datetime', ['null' => true])
                  ->addColumn('estado', 'string', ['limit' => 50])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addForeignKey('equipo_id', 'equipos', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->addForeignKey('persona_id', 'personas', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: actas
        // ============================================
        if (!$this->hasTable('actas')) {
            $table = $this->table('actas', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('numero', 'string', ['limit' => 50])
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('persona_id', 'integer')
                  ->addColumn('fecha', 'date')
                  ->addColumn('contenido', 'text')
                  ->addColumn('archivo_pdf', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['numero'], ['unique' => true])
                  ->addForeignKey('persona_id', 'personas', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                  ->create();
        }

        // ============================================
        // TABLA: secuencias_actas
        // ============================================
        if (!$this->hasTable('secuencias_actas')) {
            $table = $this->table('secuencias_actas', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('tipo', 'string', ['limit' => 50])
                  ->addColumn('prefijo', 'string', ['limit' => 10])
                  ->addColumn('numero_actual', 'integer')
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['tipo'], ['unique' => true])
                  ->create();
        }

        // ============================================
        // TABLA: configuracion
        // ============================================
        if (!$this->hasTable('configuracion')) {
            $table = $this->table('configuracion', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('clave', 'string', ['limit' => 100])
                  ->addColumn('valor', 'text')
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addIndex(['clave'], ['unique' => true])
                  ->create();
        }

        // ============================================
        // TABLA: logs
        // ============================================
        if (!$this->hasTable('logs')) {
            $table = $this->table('logs', ['id' => false, 'primary_key' => ['id']]);
            $table->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('usuario_id', 'integer', ['null' => true])
                  ->addColumn('accion', 'string', ['limit' => 100])
                  ->addColumn('tabla', 'string', ['limit' => 100, 'null' => true])
                  ->addColumn('registro_id', 'integer', ['null' => true])
                  ->addColumn('datos', 'text', ['null' => true])
                  ->addColumn('ip', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('created_at', 'datetime')
                  ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->create();
        }
    }
}